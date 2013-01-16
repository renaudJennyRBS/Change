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
	public function save($document)
	{
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			try
			{
				$this->applicationServices->getTransactionManager()->begin();

				$isNew = ($document->getPersistentState() == DocumentManager::STATE_NEW);
				if ($document instanceof \Change\Documents\Interfaces\Localizable)
				{
					if ($isNew)
					{
						$this->insertLocalized($document);
					}
					else
					{
						$this->updateLocalized($document);
					}
				}
				else
				{
					if ($isNew)
					{
						$this->insert($document);
					}
					else
					{
						$this->update($document);
					}
				}
				$this->applicationServices->getTransactionManager()->commit();
			}
			catch (\Exception $e)
			{
				throw $this->applicationServices->getTransactionManager()->rollBack($e);
			}
		}
		else
		{
			throw new \InvalidArgumentException('Invalid document');
		}
	}
	
	protected function insert(\Change\Documents\AbstractDocument $document)
	{
		$dm = $document->getDocumentManager();
		if ($document->isValid())
		{
			$dm->affectId($document);
			$document->setModificationDate(new \DateTime());
			$dm->insertDocument($document);
		}
		else
		{
			throw new \LogicException('Document is not valid');
		}
	}
	
	protected function update(\Change\Documents\AbstractDocument $document)
	{
		$dm = $document->getDocumentManager();
		if ($document->isValid())
		{
			if ($document->hasModifiedProperties())
			{
				$document->setModificationDate(new \DateTime());
			}
			$dm->updateDocument($document);
		}
		else
		{
			throw new \LogicException('Document is not valid');
		}
	}
	
	protected function insertLocalized(\Change\Documents\AbstractDocument $document)
	{
		$dm = $document->getDocumentManager();
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{	
			if ($document->getVoLCID() === null) {$document->setVoLCID($dm->getLCID());}
			try 
			{
				$dm->pushLCID($document->getVoLCID());
				if ($document->isValid())
				{
					$dm->affectId($document);
					$document->setModificationDate(new \DateTime());
					$dm->insertDocument($document);
					$dm->insertI18nDocument($document, $document->getCurrentI18nPart());
				}
				else
				{
					throw new \LogicException('Document is not valid');
				}
				$dm->popLCID();
			} 
			catch (\Exception $e)
			{
				$dm->popLCID($e);
			}
		}
	}
	
	protected function updateLocalized(\Change\Documents\AbstractDocument $document)
	{
		$dm = $document->getDocumentManager();
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$dm->pushLCID($document->getVoLCID());
			try
			{
				if ($document->isValid())
				{
					$i18nPart = $document->getCurrentI18nPart();
					if ($document->hasModifiedProperties() || $i18nPart->hasModifiedProperties())
					{
						$document->setModificationDate(new \DateTime());
					}
					$dm->updateDocument($document);
					$dm->updateI18nDocument($document, $i18nPart);
				}
				else
				{
					throw new \LogicException('Document is not valid');
				}
				$dm->popLCID();
			}
			catch (\Exception $e)
			{
				$dm->popLCID($e);
			}
		}
	}
}