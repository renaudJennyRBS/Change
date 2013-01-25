<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractService
 */
abstract class AbstractService
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
	/**
	 * \Change\Documents\DocumentServices
	 */
	protected $documentServices;
	
	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function __construct(\Change\Application\ApplicationServices $applicationServices, \Change\Documents\DocumentServices $documentServices)
	{
		$this->applicationServices = $applicationServices;
		$this->documentServices = $documentServices;
	}
	
	/**
	 * @return string
	 */
	public abstract function getModelName();
	
	/**
	 * @return \Change\Documents\Constraints\ConstraintsManager
	 */
	public function getConstraintsManager()
	{
		return $this->documentServices->getConstraintsManager();
	}
	
	/**
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocumentModel()
	{
		return $this->documentServices->getModelManager()->getModelByName($this->getModelName());
	}
	
	/**
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getNewDocumentInstance()
	{
		return $this->documentServices->getDocumentManager()->getNewDocumentInstanceByModelName($this->getModelName());
	}
	
	/**
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
	 * Called everytime a property has changed.
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $propertyName Name of the property that has changed.
	 */
	public function propertyChanged($document, $propertyName)
	{
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function create(\Change\Documents\AbstractDocument $document)
	{
		$dm = $document->getDocumentManager();
		$tm = $this->applicationServices->getTransactionManager();
		$persistentState = $document->getPersistentState();
		try
		{
			$tm->begin();
			if ($document instanceof \Change\Documents\Interfaces\Localizable)
			{
				$i18nPart = $document->getCurrentI18nPart();
				if ($i18nPart->getPersistentState() !== DocumentManager::STATE_NEW)
				{
					throw new \LogicException('Document is not new');
				}
				if ($i18nPart->getCreationDate() === null)
				{
					$i18nPart->setCreationDate(new \DateTime());
				}
				$i18nPart->setModificationDate(new \DateTime());
				if ($persistentState === DocumentManager::STATE_NEW)
				{
					$document->setVoLCID($dm->getLCID());
				}
				
				if ($document->isValid())
				{
					if ($persistentState === DocumentManager::STATE_NEW)
					{
						$dm->affectId($document);
						$dm->insertDocument($document);
					}
					elseif ($persistentState === DocumentManager::STATE_LOADED)
					{
						$dm->updateDocument($document);
					}
					
					$dm->insertI18nDocument($document, $i18nPart);
				}
				else
				{
					throw new \LogicException('Document is not valid');
				}
			}
			elseif ($persistentState === DocumentManager::STATE_NEW)
			{
				if ($document->getCreationDate() === null)
				{
					$document->setCreationDate(new \DateTime());
				}
				$document->setModificationDate(new \DateTime());
				if ($document->isValid())
				{
					$dm->affectId($document);
					$dm->insertDocument($document);
				}
				else
				{
					throw new \LogicException('Document is not valid');
				}
			}
			else
			{
				throw new \LogicException('Document is not new');
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
	 */
	public function update(\Change\Documents\AbstractDocument $document)
	{
		$dm = $document->getDocumentManager();
		$tm = $this->applicationServices->getTransactionManager();
		$persistentState = $document->getPersistentState();
		try
		{
			$tm->begin();
			
			if ($document instanceof \Change\Documents\Interfaces\Localizable)
			{
				$i18nPart = $document->getCurrentI18nPart();
				if ($i18nPart->getPersistentState() === DocumentManager::STATE_NEW)
				{
					throw new \LogicException('Document is new');
				}
				$i18nPart->setModificationDate(new \DateTime());
				if ($document->isValid())
				{
					$dm->updateDocument($document);
					$dm->updateI18nDocument($document, $i18nPart);
				}
				else
				{
					throw new \LogicException('Document is not valid');
				}
			}
			elseif ($persistentState !== DocumentManager::STATE_NEW)
			{
				$document->setModificationDate(new \DateTime());
				if ($document->isValid())
				{
					$dm->updateDocument($document);
				}
				else
				{
					throw new \LogicException('Document is not valid');
				}
			}
			else
			{
				throw new \LogicException('Document is new');
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
	 * @param array $metas
	 */
	public function saveMetas($document, $metas)
	{
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$document->getDocumentManager()->saveMetas($document, $metas);
		}
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function delete(\Change\Documents\AbstractDocument $document)
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
			
			if ($persistentState === DocumentManager::STATE_LOADED)
			{
				$dm->deleteDocument($document);
				
				if ($document instanceof \Change\Documents\Interfaces\Localizable)
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
	 * @param \Change\Documents\AbstractDocument $document
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
			if ($val instanceof \Change\Documents\AbstractDocument)
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
					if ($doc instanceof \Change\Documents\AbstractDocument)
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
		
		if (count($localized) && $document instanceof \Change\Documents\Interfaces\Localizable)
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