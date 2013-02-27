<?php
namespace Change\Documents;

use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Publishable;
use Change\Documents\Interfaces\Versionable;

/**
 * @name \Change\Documents\AbstractService
 * @api
 */
abstract class AbstractService
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @return string
	 */
	public abstract function getModelName();

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}
	
	/**
	 * @api
	 * @return \Change\Documents\Constraints\ConstraintsManager
	 */
	public function getConstraintsManager()
	{
		return $this->documentServices->getConstraintsManager();
	}
	
	/**
	 * @api
	 * @return \Change\Documents\AbstractModel
	 */
	public function getDocumentModel()
	{
		return $this->documentServices->getModelManager()->getModelByName($this->getModelName());
	}
	
	/**
	 * @api
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getNewDocumentInstance()
	{
		return $this->documentServices->getDocumentManager()->getNewDocumentInstanceByModelName($this->getModelName());
	}
	
	/**
	 * @api
	 * @throws \InvalidArgumentException
	 * @param integer $documentId
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocumentInstance($documentId)
	{
		$document = $this->documentServices->getDocumentManager()->getDocumentInstance($documentId, $this->getDocumentModel());
		if ($document === null)
		{
			throw new \InvalidArgumentException('Document id: ' . $documentId . ' not found', 51000);
		}
		return $document;
	}
	
	/**
	 * Called every time a property has changed.
	 * @api
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $propertyName Name of the property that has changed.
	 */
	public function propertyChanged($document, $propertyName)
	{
	}

	/**
	 * Called every time a document must be inserted or updateed.
	 * @api
	 * @param \Change\Documents\AbstractDocument $document
	 */
	protected function completeDocumentProperties($document)
	{

	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 * @throws \LogicException
	 */
	protected function checkCreatePersistentState($document, $localizedPart = null)
	{
		if ($localizedPart)
		{
			if ($localizedPart->getPersistentState() !== DocumentManager::STATE_NEW)
			{
				throw new \LogicException('Document is not new', 51001);
			}
			if ($document->getPersistentState() === DocumentManager::STATE_NEW)
			{
				$document->setRefLCID($localizedPart->getLCID());
			}
		}
		else
		{
			if ($document->getPersistentState() !== DocumentManager::STATE_NEW)
			{
				throw new \LogicException('Document is not new', 51001);
			}
		}
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractDocument $document
	 * @throws \LogicException
	 * @throws \Exception
	 */
	public function create(AbstractDocument $document)
	{
		$localizedPart = ($document instanceof Localizable) ? $document->getCurrentLocalizedPart() : null;
		$this->checkCreatePersistentState($document, $localizedPart);

		if ($document->getCreationDate() === null)
		{
			$document->setCreationDate(new \DateTime());
		}
		$document->setModificationDate(new \DateTime());

		$this->completeDocumentProperties($document);

		if (!$document->isValid())
		{
			throw new \LogicException('Document is not valid', 52000);
		}

		$dm = $document->getDocumentManager();
		$tm = $this->applicationServices->getTransactionManager();

		try
		{
			$tm->begin();
			if ($document->getPersistentState() === DocumentManager::STATE_NEW)
			{
				$dm->affectId($document);
				$dm->insertDocument($document);
			}
			if ($localizedPart)
			{
				$dm->insertLocalizedDocument($document, $localizedPart);
			}

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 * @throws \LogicException
	 */
	protected function checkUpdatePersistentState($document, $localizedPart = null)
	{
		if ($localizedPart)
		{
			if ($localizedPart->getPersistentState() === DocumentManager::STATE_NEW)
			{
				throw new \LogicException('Document is new', 51002);
			}
		}
		else
		{
			if ($document->getPersistentState() === DocumentManager::STATE_NEW)
			{
				throw new \LogicException('Document is new', 51002);
			}
		}
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractDocument $document
	 * @throws \LogicException
	 * @throws \Exception
	 */
	public function update(AbstractDocument $document)
	{
		$localizedPart = ($document instanceof Localizable) ? $document->getCurrentLocalizedPart() : null;
		$this->checkUpdatePersistentState($document, $localizedPart);
		
		if ($document->getModificationDate() === null)
		{
			$document->setModificationDate(new \DateTime());
		}
		
		$this->completeDocumentProperties($document);
		
		if ($document instanceof Editable)
		{
			$oldVersion = $document->getDocumentVersionOldValue();
			if ($oldVersion !== null)
			{
				throw new \LogicException('Invalid Document Version: ' . $document->getDocumentVersion() .  ' > ' . $oldVersion, 52001);
			}
			$document->setDocumentVersion($document->getDocumentVersion() + 1);
		}
		
		$publicationStatus = ($document instanceof Publishable) ? $document->getPublicationStatus() : null;
		
		/* @var $document \Change\Documents\AbstractDocument */
		if (!$document->isValid())
		{
			throw new \LogicException('Document is not valid', 52000);
		}
		
		if ($document->getDocumentModel()->useCorrection())
		{
			$corrections = $this->populateCorrections($document, $localizedPart, $publicationStatus);
		}
		else
		{
			$corrections = array();
		}

		$dm = $document->getDocumentManager();
		$tm = $this->applicationServices->getTransactionManager();
		
		try
		{
			$tm->begin();
			
			foreach ($corrections as $correction)
			{
				/* @var $correction \Change\Documents\Correction */
				$dm->saveCorrection($correction);
				foreach ($correction->getPropertiesNames() as $propertyName)
				{
					$document->removeOldPropertyValue($propertyName);
				}
			}

			if ($document->hasModifiedProperties() || count($corrections))
			{
				$document->setModificationDate(new \DateTime());
				
				if ($document->hasNonLocalizedModifiedProperties())
				{
					$dm->updateDocument($document);
				}
				
				if ($document instanceof Localizable)
				{
					$localizedPart = $document->getCurrentLocalizedPart();
					if ($localizedPart->hasModifiedProperties())
					{
						$dm->updateLocalizedDocument($document, $localizedPart);
					}
				}
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
	
	/**
	 * @throws \LogicException
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\AbstractLocalizedDocument $localizedPart
	 * @param string $publicationStatus
	 * @return \Change\Documents\Correction[]
	 */
	protected function populateCorrections($document, $localizedPart, $publicationStatus)
	{
		if ($publicationStatus === Publishable::STATUS_DRAFT)
		{
			return array();
		}
		
		$values = array();
		
		$nonLocalizedKey = ($document instanceof Localizable) ? $document->getRefLCID() : Correction::NULL_LCID_KEY;
			
		foreach ($document->getDocumentModel()->getPropertiesWithCorrection() as $propertyName => $property)
		{
			if ($document->isPropertyModified($propertyName))
			{
				if ($publicationStatus === Publishable::STATUS_VALIDATION)
				{
					throw new \LogicException('Unable to create correction for document in VALIDATION state', 51003);
				}
				
				/* @var $property \Change\Documents\Property */
				if ($property->getLocalized())
				{
					$values[$localizedPart->getLCID()][$propertyName] = $property->getValue($localizedPart);
				}
				else
				{
					$values[$nonLocalizedKey][$propertyName] = $property->getValue($document);
				}
			}
		}
		
		$corrections = array();
		foreach ($values as $LCID => $cValues)
		{
			$correction = $document->getCorrection(($LCID === Correction::NULL_LCID_KEY) ? null : $LCID);
			if ($correction->getStatus() === Correction::STATUS_VALIDATION)
			{
				throw new \LogicException('Unable to update correction in VALIDATION state', 51004);
			}
			
			if ($publicationStatus === null)
			{
				$correction->setStatus(Correction::STATUS_PUBLISHABLE);
			}
			foreach ($cValues as $name => $value)
			{
				$correction->setPropertyValue($name, $value);
			}
			$corrections[$LCID] = $correction;
		}
		
		return array_values($corrections);
	}
	
	/**
	 * @api
	 * @throws \InvalidArgumentException
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\Correction $correction
	 */
	public function applyCorrection(AbstractDocument $document, Correction $correction)
	{
		if ($document->getId() != $correction->getDocumentId() || $correction->getStatus() != Correction::STATUS_PUBLISHABLE)
		{
			throw new \InvalidArgumentException('Correction '. $correction .  ' not applicable to Document ' . $document, 51005);
		}
	
		if ($correction->getPublicationDate() != null && $correction->getPublicationDate() > new \DateTime())
		{
			throw new \InvalidArgumentException('Correction '. $correction .  ' not publishable', 51006);
		}
	
		if ($correction->getLcid() !== null)
		{
			$dm = $document->getDocumentManager();
			try
			{
				$dm->pushLCID($correction->getLcid());
				if ($document->hasModifiedProperties())
				{
					throw new \InvalidArgumentException('Document '. $document .  ' is already modified', 51007);
				}
				$this->applyCorrectionInternal($document, $correction);
				$dm->popLCID();
			}
			catch (\Exception $e)
			{
				$dm->popLCID($e);
			}
		}
		else
		{
			if ($document->hasModifiedProperties())
			{
				throw new \InvalidArgumentException('Document '. $document .  ' is already modified', 51007);
			}				
			$this->applyCorrectionInternal($document, $correction);
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\Correction $correction
	 * @throws \Exception
	 */
	protected function applyCorrectionInternal($document, $correction)
	{
		$model = $document->getDocumentModel();
		foreach ($model->getProperties() as $propertyName => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($correction->isModifiedProperty($propertyName))
			{
				$property->setValue($document, $correction->getPropertyValue($propertyName));
				if ($document->isPropertyModified($propertyName))
				{
					$oldValue = $property->getOldValue($document);
					$correction->setPropertyValue($propertyName, $oldValue);
				}
				else
				{
					$correction->unsetPropertyValue($propertyName);
				}
			}
		}
	
		if ($document->hasModifiedProperties())
		{
			$document->setModificationDate(new \DateTime());
			if ($document instanceof Editable)
			{
				$document->setDocumentVersion($document->getDocumentVersion() + 1);
			}
		}
		
		$correction->setStatus(Correction::STATUS_FILED);
		$correction->setPublicationDate(new \DateTime());
			
		$dm = $document->getDocumentManager();
		$tm = $this->applicationServices->getTransactionManager();
			
		try
		{
			$tm->begin();
			
			if ($document->hasNonLocalizedModifiedProperties())
			{
				$dm->updateDocument($document);
			}
			
			if ($document instanceof Localizable)
			{
				$localizedPart = $document->getCurrentLocalizedPart();
				if ($localizedPart->hasModifiedProperties())
				{
					$dm->updateLocalizedDocument($document, $localizedPart);
				}
			}

			$dm->saveCorrection($correction);
			
			$document->removeCorrection($correction);

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
	
	/**
	 * @api
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $metas
	 */
	public function saveMetas($document, $metas)
	{
		if ($document instanceof AbstractDocument)
		{
			$document->getDocumentManager()->saveMetas($document, $metas);
		}
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractDocument $document
	 * @throws \Exception
	 */
	public function delete(AbstractDocument $document)
	{
		$dm = $document->getDocumentManager();
		$tm = $this->applicationServices->getTransactionManager();
		$persistentState = $document->getPersistentState();
		if ($persistentState === DocumentManager::STATE_DELETED)
		{
			return;
		}
		
		try
		{
			$tm->begin();
			$backupData = $this->generateBackupData($document);
			if (is_array($backupData) && count($backupData))
			{
				$dm->insertDocumentBackup($document, $backupData);
			}

			if ($persistentState === DocumentManager::STATE_INITIALIZED || $persistentState === DocumentManager::STATE_LOADED)
			{
				$dm->deleteDocument($document);
				
				if ($document instanceof Localizable)
				{
					$dm->deleteLocalizedDocuments($document);
				}
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
	
	/**
	 * @api
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array
	 */
	public function generateBackupData($document)
	{
		$datas = array();
		$datas['metas'] = $document->getMetas();
		if ($document->getTreeName())
		{
			$node = $this->documentServices->getTreeManager()->getNodeByDocument($document);
			if ($node)
			{
				$datas['treeName'] = array($document->getTreeName(), $node->getParentId());
			}
		}
		
		$localized = array();
		foreach ($document->getDocumentModel()->getProperties() as $propertyName => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getLocalized())
			{
				$localized[$propertyName] = $property;
				continue;
			}
			$val = $property->getValue($document);
			if ($val instanceof AbstractDocument)
			{
				$datas[$propertyName] = array($val->getId(), $val->getDocumentModelName());
			}
			elseif ($val instanceof \DateTime)
			{
				$datas[$propertyName] = $val->format('c');
			}
			elseif (is_array($val))
			{
				foreach ($val as $doc)
				{
					if ($doc instanceof AbstractDocument)
					{
						$datas[$propertyName][] = array($doc->getId(), $doc->getDocumentModelName());
					}
				}
			}
			else
			{
				$datas[$propertyName] = $val;
			}
		}

		$dm = $document->getDocumentManager();
		if (count($localized) && $document instanceof Localizable)
		{
			$datas['LCID'] = array();
			foreach ($document->getLocalizableFunctions()->getLCIDArray() as $LCID)
			{
				$dm->pushLCID($LCID);
				$datas['LCID'][$LCID] = array();
				foreach ($localized as $propertyName => $property)
				{
					/* @var $property \Change\Documents\Property */
					$val = $property->getValue($document);
					if ($val instanceof \DateTime)
					{
						$datas[$propertyName] = $val->format('c');
					}
					else
					{
						$datas['LCID'][$LCID][$propertyName] = $val;
					}
				}
				$dm->popLCID();
			}
		}
		return $datas;
	}
}