<?php
namespace Change\Documents;

use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Change\Documents\AbstractDocument
 * @api
 */
abstract class AbstractDocument implements \Serializable, \Zend\EventManager\EventsCapableInterface
{		
	/**
	 * @var integer
	 */
	private $persistentState = DocumentManager::STATE_NEW;

	/**
	 * @var integer
	 */
	private $id = 0;
	
	/**
	 * @var string
	 */
	private $documentModelName;

	/**
	 * @var array
	 */
	private $modifiedProperties = array();

	/**
	 * @var array<String,String|String[]>
	 */
	private $metas;
	
	/**
	 * @var boolean
	 */
	private $modifiedMetas = false;

	/**
	 * @var \Change\Documents\CorrectionFunctions
	 */
	protected $correctionFunctions;

	/**
	 * @var \Change\Documents\AbstractModel
	 */
	protected $documentModel;
	
	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var \Zend\EventManager\EventManager
	 */
	protected $eventManager;

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Documents\AbstractModel $model
	 */
	public function __construct(\Change\Documents\DocumentServices $documentServices, \Change\Documents\AbstractModel $model)
	{
		$this->setDocumentContext($documentServices, $model);
	}

	public function __destruct()
	{
		unset($this->documentServices);
		unset($this->documentModel);
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Documents\AbstractModel $model
	 * @return void
	 */
	public function setDocumentContext(\Change\Documents\DocumentServices $documentServices, \Change\Documents\AbstractModel $model)
	{
		$this->documentServices = $documentServices;
		$this->documentModel = $model;
		$this->documentModelName = $model->getName();

	}

	/**
	 * This class is not serializable
	 * @return null
	 */
	public function serialize()
	{
		return null;
	}

	/**
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
		return;
	}

	/**
	 * @api
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @api
	 * @return \Change\Documents\AbstractModel
	 */
	public function getDocumentModel()
	{
		return $this->documentModel;
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getDocumentModelName()
	{
		return $this->documentModelName;
	}

	/**
	 * Retrieve the event manager
	 * @api
	 * @return \Zend\EventManager\EventManagerInterface
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$model = $this->getDocumentModel();
			$identifiers = array_merge($model->getAncestorsNames(), array($model->getName(), 'Documents'));
			$eventManager = new \Zend\EventManager\EventManager($identifiers);
			$eventManager->setSharedManager($this->getDocumentManager()->getApplicationServices()->getApplication()->getSharedEventManager());
			$eventManager->setEventClass('\Change\Documents\Events\Event');
			$this->eventManager = $eventManager;
		}
		return $this->eventManager;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentServices->getDocumentManager();
	}

	/**
	 * @param integer $id
	 * @param integer $persistentState
	 */
	public function initialize($id, $persistentState = null)
	{
		$oldId = $this->id;
		$this->id = intval($id);
		if ($persistentState!== null)
		{
			$this->setPersistentState($persistentState);
		}
		$this->getDocumentManager()->reference($this, $oldId);
	}

	/**
	 * @api
	 */
	public function reset()
	{
		$this->modifiedProperties = array();
		$this->metas = null;
		$this->modifiedMetas = false;
		$this->correctionFunctions = null;

		if ($this->persistentState === DocumentManager::STATE_LOADED)
		{
			$this->persistentState = DocumentManager::STATE_INITIALIZED;
		}
		elseif($this->persistentState === DocumentManager::STATE_NEW)
		{
			$this->setDefaultValues($this->documentModel);
		}
	}

	/**
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	public function setDefaultValues(\Change\Documents\AbstractModel $documentModel)
	{
		$this->persistentState = DocumentManager::STATE_NEW;
		foreach ($documentModel->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			if (!$property->getLocalized() && $property->getDefaultValue() !== null)
			{
				$property->setValue($this, $property->getDefaultValue());
			}
		}
		$this->clearModifiedProperties();
	}
	
	/**
	 * Persistent state list: \Change\Documents\DocumentManager::STATE_*
	 * @api
	 * @return integer
	 */
	public function getPersistentState()
	{
		return $this->persistentState;
	}

	/**
	 * Persistent state list: \Change\Documents\DocumentManager::STATE_*
	 * @api
	 * @param integer $newValue
	 * @return integer oldState
	 */
	public function setPersistentState($newValue)
	{
		$oldState = $this->persistentState;
		switch ($newValue) 
		{
			case DocumentManager::STATE_LOADED:
				$this->clearModifiedProperties();
			case DocumentManager::STATE_NEW:
			case DocumentManager::STATE_INITIALIZED:
			case DocumentManager::STATE_LOADING:	
			case DocumentManager::STATE_DELETED:
			case DocumentManager::STATE_SAVING:
				$this->persistentState = $newValue;
				break;
		}
		return $oldState;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isDeleted()
	{
		return $this->persistentState === DocumentManager::STATE_DELETED;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isNew()
	{
		return $this->persistentState === DocumentManager::STATE_NEW;
	}
	
	/**
	 * @api
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	protected function checkLoaded()
	{
		if ($this->persistentState === DocumentManager::STATE_INITIALIZED)
		{
			$this->getDocumentManager()->loadDocument($this);
		}
	}

	/**
	 * @api
	 */
	public function save()
	{
		if ($this->isNew())
		{
			$this->create();
		}
		else
		{
			$this->update();
		}
	}
	
	/**
	 * @api
	 */
	public function create()
	{
		if (!$this->isNew())
		{
			throw new \RuntimeException('Document is not new', 51001);
		}

		$event = new DocumentEvent(DocumentEvent::EVENT_CREATE, $this);
		$this->getEventManager()->trigger($event);

		$propertiesErrors = $event->getParam('propertiesErrors');
		if (is_array($propertiesErrors) && count($propertiesErrors))
		{
			$e = new \RuntimeException('Document is not valid', 52000);
			$e->propertiesErrors = $propertiesErrors;
			throw $e;
		}

		$tm = $this->getDocumentServices()->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$this->doCreate();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doCreate()
	{
		$dm = $this->getDocumentManager();

		if ($this->getPersistentState() === DocumentManager::STATE_NEW)
		{
			$dm->affectId($this);
			$dm->insertDocument($this);
		}

		if ($this instanceof \Change\Documents\Interfaces\Localizable)
		{
			$dm->insertLocalizedDocument($this, $this->getCurrentLocalizedPart());
		}
	}
	
	/**
	 * @api
	 */
	public function update()
	{
		if ($this->isNew())
		{
			throw new \RuntimeException('Document is new', 51002);
		}

		$event = new DocumentEvent(DocumentEvent::EVENT_UPDATE, $this);
		$this->getEventManager()->trigger($event);

		$propertiesErrors = $event->getParam('propertiesErrors');
		if (is_array($propertiesErrors) && count($propertiesErrors))
		{
			$e = new \RuntimeException('Document is not valid', 52000);
			$e->propertiesErrors = $propertiesErrors;
			throw $e;
		}

		$tm = $this->getDocumentServices()->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$this->doUpdate();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doUpdate()
	{
		if ($this->getDocumentModel()->useCorrection())
		{
			$corrections = $this->getCorrectionFunctions()->extractCorrections();
		}
		else
		{
			$corrections = array();
		}

		$dm = $this->getDocumentManager();
		if (count($corrections))
		{
			$cleanUpPropertiesNames = array();
			foreach ($corrections as $correction)
			{
				/* @var $correction \Change\Documents\Correction */
				$this->getCorrectionFunctions()->save($correction);
				$cleanUpPropertiesNames = array_merge($cleanUpPropertiesNames, $correction->getPropertiesNames());
			}

			foreach (array_unique($cleanUpPropertiesNames) as $propertyName)
			{
				$this->removeOldPropertyValue($propertyName);
			}

		}

		if ($this->hasModifiedProperties() || count($corrections))
		{
			$this->setModificationDate(new \DateTime());
			if ($this instanceof \Change\Documents\Interfaces\Editable)
			{
				$this->nextDocumentVersion();
			}

			if ($this->hasNonLocalizedModifiedProperties())
			{
				$dm->updateDocument($this);
			}

			if ($this instanceof \Change\Documents\Interfaces\Localizable)
			{
				$localizedPart = $this->getCurrentLocalizedPart();
				if ($localizedPart->hasModifiedProperties())
				{
					$dm->updateLocalizedDocument($this, $localizedPart);
				}
			}
		}
	}

	/**
	 * @api
	 */
	public function delete()
	{
		//Already deleted
		if ($this->getPersistentState() === DocumentManager::STATE_DELETED)
		{
			return;
		}

		if ($this->getPersistentState() === DocumentManager::STATE_NEW )
		{
			throw new \RuntimeException('Document is new', 51002);
		}

		$event = new DocumentEvent(DocumentEvent::EVENT_DELETE, $this);
		$this->getEventManager()->trigger($event);

		$tm = $this->getDocumentServices()->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$backupData = $event->getParam('backupData');
			if (is_array($backupData) && count($backupData))
			{
				$this->getDocumentManager()->insertDocumentBackup($this, $backupData);
			}

			$this->doDelete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @throws \Exception
	 */
	protected function doDelete()
	{

		$dm = $this->getDocumentManager();
		$dm->deleteDocument($this);
		if ($this instanceof \Change\Documents\Interfaces\Localizable)
		{
			$dm->deleteLocalizedDocuments($this);
		}
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasNonLocalizedModifiedProperties()
	{
		return count($this->modifiedProperties) > 0;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasModifiedProperties()
	{
		return $this->hasNonLocalizedModifiedProperties();
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getModifiedPropertyNames()
	{
		return array_keys($this->modifiedProperties);
	}
	
	/**
	 * @api
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isPropertyModified($propertyName)
	{
		return array_key_exists($propertyName, $this->modifiedProperties);
	}

	/**
	 * @param string $propertyName
	 * @return mixed
	 */
	protected function getOldPropertyValue($propertyName)
	{
		if (array_key_exists($propertyName, $this->modifiedProperties))
		{
			return $this->modifiedProperties[$propertyName];
		}
		return null;
	}

	/**
	 * @api
	 */
	protected function clearModifiedProperties()
	{
		$this->modifiedProperties = array();
	}
		
	/**
	 * @param string $propertyName
	 * @param mixed $value
	 */
	protected function setOldPropertyValue($propertyName, $value)
	{
		if (!array_key_exists($propertyName, $this->modifiedProperties))
		{
			$this->modifiedProperties[$propertyName] = $value;
		}
	}
	
	/**
	 * @api
	 * @param string $propertyName
	 */
	public function removeOldPropertyValue($propertyName)
	{
		if (array_key_exists($propertyName, $this->modifiedProperties))
		{
			unset($this->modifiedProperties[$propertyName]);
		}
	}

	/**
	 * Called every time a property has changed.
	 * @param string $propertyName Name of the property that has changed.
	 */
	protected function propertyChanged($propertyName)
	{

	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractDocument $b
	 * @return boolean
	 */
	public function equals($b)
	{
		return $this === $b || (($b instanceof AbstractDocument) && $b->getId() === $this->getId());
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getDocumentModelName().' '.$this->getId();
	}
		
	// Metadata management

	/**
	 * @api
	 */
	public function saveMetas()
	{
		if ($this->modifiedMetas)
		{
			$this->getDocumentManager()->saveMetas($this, $this->metas);
			$this->modifiedMetas = false;
		}
	}
	
	/**
	 * @return void
	 */
	protected function checkMetasLoaded()
	{
		if ($this->metas === null)
		{
			$this->metas = $this->getDocumentManager()->loadMetas($this);
			$this->modifiedMetas = false;
		}
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function hasModifiedMetas()
	{
		return $this->modifiedMetas;
	}
		
	/**
	 * @api
	 * @return array
	 */
	public function getMetas()
	{
		$this->checkMetasLoaded();
		return $this->metas;
	}
	
	/**
	 * @api
	 * @param array $metas
	 */
	public function setMetas($metas)
	{
		$this->checkMetasLoaded();
		if (count($this->metas))
		{
			$this->metas = array();
			$this->modifiedMetas = true;
		}
		if (is_array($metas))
		{
			foreach ($metas as $name => $value)
			{
				$this->metas[$name] = $value;
			}
			$this->modifiedMetas = true;
		}
	}
	
	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function hasMeta($name)
	{
		$this->checkMetasLoaded();
		return isset($this->metas[$name]);
	}

	/**
	 * @api
	 * @param string $name
	 * @return mixed
	 */
	public function getMeta($name)
	{
		$this->checkMetasLoaded();
		return isset($this->metas[$name]) ? $this->metas[$name] : null;
	}

	/**
	 * @api
	 * @param string $name
	 * @param mixed|null $value
	 */
	public function setMeta($name, $value)
	{
		$this->checkMetasLoaded();
		if ($value === null)
		{
			if (isset($this->metas[$name]))
			{
				unset($this->metas[$name]);
				$this->modifiedMetas = true;
			}
		}
		elseif (!isset($this->metas[$name]) || $this->metas[$name] != $value)
		{
			$this->metas[$name] = $value;
			$this->modifiedMetas = true;
		}
	}
	
	// Correction Method

	/**
	 * @return \Change\Documents\CorrectionFunctions
	 */
	public function getCorrectionFunctions()
	{
		if ($this->correctionFunctions === null)
		{
			$this->correctionFunctions = new CorrectionFunctions($this);
		}
		return $this->correctionFunctions;
	}

	// Generic Method

	/**
	 * @param string|null $treeName
	 */
	public function setTreeName($treeName)
	{
		return;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getTreeName()
	{
		return null;
	}
	
	/**
	 * @api
	 * @return \DateTime
	 */
	abstract public function getCreationDate();
	
	/**
	 * @api
	 * @param \DateTime $creationDate
	 */
	abstract public function setCreationDate($creationDate);
	
	/**
	 * @api
	 * @return \DateTime
	 */
	abstract public function getModificationDate();
	
	/**
	 * @api
	 * @param \DateTime $modificationDate
	 */
	abstract public function setModificationDate($modificationDate);
}