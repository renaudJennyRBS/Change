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
			throw new \InvalidArgumentException('Document id: ' . $documentId . ' not found');
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
	 * @param \Change\Documents\AbstractI18nDocument $i18nPart
	 * @throws \LogicException
	 */
	protected function checkCreatePersistentState($document, $i18nPart = null)
	{
		if ($i18nPart)
		{
			if ($i18nPart->getPersistentState() !== DocumentManager::STATE_NEW)
			{
				throw new \LogicException('Document is not new');
			}
			if ($document->getPersistentState() === DocumentManager::STATE_NEW)
			{
				$document->setRefLCID($i18nPart->getLCID());
			}
		}
		else
		{
			if ($document->getPersistentState() !== DocumentManager::STATE_NEW)
			{
				throw new \LogicException('Document is not new');
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
		$i18nPart = ($document instanceof Localizable) ? $document->getCurrentI18nPart() : null;
		$this->checkCreatePersistentState($document, $i18nPart);

		if ($document->getCreationDate() === null)
		{
			$document->setCreationDate(new \DateTime());
		}
		$document->setModificationDate(new \DateTime());

		$this->completeDocumentProperties($document);

		if (!$document->isValid())
		{
			throw new \LogicException('Document is not valid');
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
			if ($i18nPart)
			{
				$dm->insertI18nDocument($document, $i18nPart);
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
	 * @param \Change\Documents\AbstractI18nDocument $i18nPart
	 * @throws \LogicException
	 */
	protected function checkUpdatePersistentState($document, $i18nPart = null)
	{
		if ($i18nPart)
		{
			if ($i18nPart->getPersistentState() === DocumentManager::STATE_NEW)
			{
				throw new \LogicException('Document is new');
			}
		}
		else
		{
			if ($document->getPersistentState() === DocumentManager::STATE_NEW)
			{
				throw new \LogicException('Document is new');
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
		$i18nPart = ($document instanceof Localizable) ? $document->getCurrentI18nPart() : null;
		$this->checkUpdatePersistentState($document, $i18nPart);
		
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
				throw new \LogicException('Invalid Document Version: ' . $document->getDocumentVersion() .  ' > ' . $oldVersion);
			}
			$document->setDocumentVersion($document->getDocumentVersion() + 1);
		}
		
		$publicationStatus = ($document instanceof Publishable) ? $document->getPublicationStatus() : null;
		
		/* @var $document \Change\Documents\AbstractDocument */
		if (!$document->isValid())
		{
			throw new \LogicException('Document is not valid');
		}
		
		if ($document->getDocumentModel()->useCorrection())
		{
			$corrections = $this->populateCorrections($document, $i18nPart, $publicationStatus);
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

			if ($document->hasModifiedProperties()) 
			{
				$document->setModificationDate(new \DateTime());
				
				if ($document->hasNonLocalizedModifiedProperties())
				{
					$dm->updateDocument($document);
				}
				
				if ($document instanceof Localizable)
				{
					$i18nPart = $document->getCurrentI18nPart();
					if ($i18nPart->hasModifiedProperties())
					{
						$dm->updateI18nDocument($document, $i18nPart);
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
	 * @param \Change\Documents\AbstractI18nDocument $i18nPart
	 * @param string $publicationStatus
	 * @return \Change\Documents\Correction[]
	 */
	protected function populateCorrections($document, $i18nPart, $publicationStatus)
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
					throw new \LogicException('Unable to create correction for document in VALIDATION state');
				}
				
				/* @var $property \Change\Documents\Property */
				if ($property->getLocalized())
				{
					$values[$i18nPart->getLCID()][$propertyName] = $property->getValue($i18nPart);
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
			$correction = ($LCID === $nonLocalizedKey) ? $document->getCorrection() : $document->getLocalizedCorrection();
			if ($correction->getStatus() === Correction::STATUS_VALIDATION)
			{
				throw new \LogicException('Unable to update correction in VALIDATION state');
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
			throw new \InvalidArgumentException('Correction '. $correction .  ' not applicable to Document ' . $document);
		}
	
		if ($correction->getPublicationDate() != null && $correction->getPublicationDate() > new \DateTime())
		{
			throw new \InvalidArgumentException('Correction '. $correction .  ' not publishable');
		}
	
		if ($correction->getLcid() !== null)
		{
			$dm = $document->getDocumentManager();
			try
			{
				$dm->pushLCID($correction->getLcid());
				if ($document->hasModifiedProperties())
				{
					throw new \InvalidArgumentException('Document '. $document .  ' is already modified');
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
				throw new \InvalidArgumentException('Document '. $document .  ' is already modified');
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
				$i18nPart = $document->getCurrentI18nPart();
				if ($i18nPart->hasModifiedProperties())
				{
					$dm->updateI18nDocument($document, $i18nPart);
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
					$dm->deleteI18nDocuments($document);
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
		
		if (count($localized) && $document instanceof Localizable)
		{
			$datas['LCID'] = array();
			$dm = $document->getDocumentManager();
			foreach ($document->getLCIDArray() as $LCID)
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