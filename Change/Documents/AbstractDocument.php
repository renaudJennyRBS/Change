<?php
namespace Change\Documents;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventsCapableInterface;

/**
 * @name \Change\Documents\AbstractDocument
 * @api
 */
abstract class AbstractDocument implements \Serializable, EventsCapableInterface
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
	 * @var AbstractModel
	 */
	protected $documentModel;
	
	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var EventManager
	 */
	protected $eventManager;

	/**
	 * @param DocumentServices $documentServices
	 * @param AbstractModel $model
	 */
	public function __construct(DocumentServices $documentServices, AbstractModel $model)
	{
		$this->setDocumentContext($documentServices, $model);
	}

	public function __destruct()
	{
		unset($this->documentServices);
		unset($this->documentModel);
	}

	/**
	 * @param DocumentServices $documentServices
	 * @param AbstractModel $model
	 * @return void
	 */
	public function setDocumentContext(DocumentServices $documentServices, AbstractModel $model)
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
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @api
	 * @return AbstractModel
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
			$eventManager = new EventManager($identifiers);
			$eventManager->setSharedManager($this->getApplicationServices()->getApplication()->getSharedEventManager());
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
	 * @return \Change\Application\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->documentServices->getApplicationServices();
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
	 * @param mixed $inputValue
	 * @param string $propertyType
	 * @return bool|\DateTime|float|int|null|string
	 */
	protected function convertToInternalValue($inputValue, $propertyType)
	{
		switch ($propertyType)
		{
			case Property::TYPE_DATE:
				$inputValue = is_string($inputValue) ? new \DateTime($inputValue, new \DateTimeZone('UTC')) : $inputValue;
				return ($inputValue instanceof \DateTime) ? \DateTime::createFromFormat('Y-m-d', $inputValue->format('Y-m-d'), new \DateTimeZone('UTC'))->setTime(0, 0) : null;

			case Property::TYPE_DATETIME:
				return is_string($inputValue) ? new \DateTime($inputValue, new \DateTimeZone('UTC')): (($inputValue instanceof \DateTime) ? $inputValue : null);

			case Property::TYPE_BOOLEAN:
				return ($inputValue === null) ? $inputValue : (bool)$inputValue;

			case Property::TYPE_INTEGER:
				return ($inputValue === null) ? $inputValue : intval($inputValue);

			case Property::TYPE_FLOAT:
			case Property::TYPE_DECIMAL:
				return ($inputValue === null) ? $inputValue : floatval($inputValue);

			case Property::TYPE_DOCUMENTID :
				return ($inputValue === null) ? $inputValue : (($inputValue instanceof AbstractDocument) ? $inputValue->getId() : (intval($inputValue) > 0 ? intval($inputValue) : null));

			case Property::TYPE_JSON:
				return ($inputValue === null || is_string($inputValue)) ? $inputValue : json_encode($inputValue);

			case Property::TYPE_OBJECT:
				return ($inputValue === null || is_string($inputValue)) ? $inputValue : serialize($inputValue);

			case Property::TYPE_DOCUMENT:
			case Property::TYPE_DOCUMENTARRAY:
				return ($inputValue === null || !($inputValue instanceof AbstractDocument)) ? null : $inputValue->getId();

			default:
				return $inputValue === null ? $inputValue : strval($inputValue);
		}
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
	 * @param AbstractModel $documentModel
	 */
	public function setDefaultValues(AbstractModel $documentModel)
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

	/**
	 * Load properties
	 * @api
	 */
	public function load()
	{
		if ($this->persistentState === DocumentManager::STATE_INITIALIZED)
		{
			if ($this->documentModel->isStateless())
			{
				$callable = array($this, 'doLoadStateless');
				if (is_callable($callable))
				{
					call_user_func($callable);
				}
			}
			else
			{
				$this->doLoad();
			}
		}
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doLoad()
	{
		$this->getDocumentManager()->loadDocument($this);
		$callable = array($this, 'onLoad');
		if (is_callable($callable))
		{
			call_user_func($callable);
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
		$callable = array($this, 'onCreate');
		if (is_callable($callable))
		{
			call_user_func($callable);
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

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			if ($this->documentModel->isStateless())
			{
				$callable = array($this, 'doCreateStateless');
				if (is_callable($callable))
				{
					call_user_func($callable);
				}
			}
			else
			{
				$this->doCreate();
			}
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

		if ($this instanceof Localizable)
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

		$callable = array($this, 'onUpdate');
		if (is_callable($callable))
		{
			call_user_func($callable);
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

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			if ($this->documentModel->isStateless())
			{
				$callable = array($this, 'doUpdateStateless');
				if (is_callable($callable))
				{
					call_user_func($callable);
				}
			}
			else
			{
				$this->doUpdate();
			}
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
			if ($this instanceof Editable)
			{
				$this->nextDocumentVersion();
			}

			if ($this->hasNonLocalizedModifiedProperties())
			{
				$dm->updateDocument($this);
			}

			if ($this instanceof Localizable)
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

		$callable = array($this, 'onDelete');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}
		$event = new DocumentEvent(DocumentEvent::EVENT_DELETE, $this);
		$this->getEventManager()->trigger($event);

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			if ($this->documentModel->isStateless())
			{
				$callable = array($this, 'doDeleteStateless');
				if (is_callable($callable))
				{
					call_user_func($callable);
				}
			}
			else
			{
				$backupData = $event->getParam('backupData');
				if (is_array($backupData) && count($backupData))
				{
					try
					{
						$this->getDocumentManager()->insertDocumentBackup($this, $backupData);
					}
					catch (\Exception $e)
					{
						//Unable to backup document
						$this->documentServices->getApplicationServices()->getLogging()->exception($e);
					}
				}
				$this->doDelete();
			}
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
		if ($this instanceof Localizable)
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