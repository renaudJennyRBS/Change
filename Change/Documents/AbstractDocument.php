<?php
namespace Change\Documents;

use Change\Documents\Interfaces\Activable;
use Change\Documents\Interfaces\Correction;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\PropertyConverter;
use Change\Http\Rest\RestfulDocumentInterface;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\ErrorResult;
use Change\Http\Rest\Result\TreeNodeLink;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventsCapableInterface;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Documents\AbstractDocument
 * @api
 */
abstract class AbstractDocument implements \Serializable, EventsCapableInterface, RestfulDocumentInterface
{
	const STATE_NEW = 1;

	const STATE_INITIALIZED = 2;

	const STATE_LOADING = 3;

	const STATE_LOADED = 4;

	const STATE_SAVING = 5;

	const STATE_DELETED = 6;

	const STATE_DELETING = 7;

	/**
	 * @var integer
	 */
	private $persistentState = self::STATE_NEW;

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
	protected $modifiedProperties = array();

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
	 * @param AbstractModel $model
	 */
	public function __construct(DocumentServices $documentServices, AbstractModel $model)
	{
		$this->setDocumentContext($documentServices, $model);
	}

	public function cleanUp()
	{
		if (isset($this->eventManager))
		{
			foreach ($this->eventManager->getEvents() as $event)
			{
				$this->eventManager->clearListeners($event);
			}
			$this->eventManager = null;
		}
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
			$eventManager = new EventManager($identifiers);
			$eventManager->setSharedManager($this->getApplicationServices()->getApplication()->getSharedEventManager());
			$eventManager->setEventClass('\Change\Documents\Events\Event');
			$this->eventManager = $eventManager;
			$this->attachEvents($eventManager);
		}
		return $this->eventManager;
	}

	/**
	 * Attach specific document event
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{

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
		$this->id = intval($id);
		if ($persistentState !==  null)
		{
			$this->setPersistentState($persistentState);
		}
		$this->getDocumentManager()->reference($this);
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
				if (is_object($inputValue) && is_callable(array($inputValue, 'getId')))
				{
					$inputValue = call_user_func(array($inputValue, 'getId'));
				}
				return max(0, intval($inputValue));
			case Property::TYPE_JSON:
				return ($inputValue === null || is_string($inputValue)) ? $inputValue : json_encode($inputValue);

			case Property::TYPE_OBJECT:
				return ($inputValue === null || is_string($inputValue)) ? $inputValue : serialize($inputValue);

			case Property::TYPE_DOCUMENT:
			case Property::TYPE_DOCUMENTARRAY:
				return ($inputValue === null || !($inputValue instanceof AbstractDocument)) ? 0 : $inputValue->getId();
			default:
				return $inputValue === null ? $inputValue : strval($inputValue);
		}
	}

	/**
	 * @param float $v1
	 * @param float $v2
	 * @param float $delta
	 * @return boolean
	 */
	protected function compareFloat($v1, $v2, $delta = 0.000001)
	{
		if ($v1 === $v2)
		{
			return true;
		}
		elseif ($v1 === null || $v2 === null)
		{
			return false;
		}
		return abs(floatval($v1) - floatval($v2)) <= $delta;
	}

	/**
	 * @api
	 */
	public function reset()
	{
		$this->unsetProperties();
		if ($this->persistentState === static::STATE_LOADED)
		{
			$this->persistentState = static::STATE_INITIALIZED;
		}
		elseif($this->persistentState === static::STATE_NEW)
		{
			$this->setDefaultValues($this->documentModel);
		}
	}

	/**
	 * Set private properties to null.
	 */
	protected function unsetProperties()
	{
		$this->clearModifiedProperties();
	}

	/**
	 * @param AbstractModel $documentModel
	 */
	public function setDefaultValues(AbstractModel $documentModel)
	{
		$this->persistentState = static::STATE_NEW;
		foreach ($documentModel->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			if (!$property->getLocalized() && $property->getDefaultValue() !== null)
			{
				$property->setValue($this, $property->getDefaultValue());
			}
		}
	}
	
	/**
	 * Persistent state list: static::STATE_*
	 * @api
	 * @return integer
	 */
	public function getPersistentState()
	{
		return $this->persistentState;
	}

	/**
	 * Persistent state list: static::STATE_*
	 * @api
	 * @param integer $newValue
	 * @return integer oldState
	 */
	public function setPersistentState($newValue)
	{
		$oldState = $this->persistentState;
		switch ($newValue) 
		{
			case static::STATE_LOADED:
				$this->clearModifiedProperties();
				$this->persistentState = $newValue;
				break;
			case static::STATE_NEW:
			case static::STATE_INITIALIZED:
			case static::STATE_LOADING:
			case static::STATE_DELETING:
			case static::STATE_DELETED:
			case static::STATE_SAVING:
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
		return $this->persistentState === static::STATE_DELETED;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isNew()
	{
		return $this->persistentState === static::STATE_NEW;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isLoaded()
	{
		return $this->persistentState === static::STATE_LOADED;
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
	 * @api
	 * @param string $propertyName
	 */
	public function removeOldPropertyValue($propertyName)
	{
		unset($this->modifiedProperties[$propertyName]);
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
	 * @return boolean
	 */
	public final function hasModifiedProperties()
	{
		return count($this->getModifiedPropertyNames()) !== 0;
	}
	
	/**
	 * @api
	 * @param string $propertyName
	 * @return boolean
	 */
	public final function isPropertyModified($propertyName)
	{
		return in_array($propertyName, $this->getModifiedPropertyNames());
	}

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

	// Tree

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

	// Generic Method

	abstract public function load();

	abstract public function save();

	abstract public function update();

	abstract public function create();

	abstract public function delete();

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $documentResult
	 */
	public function populateRestDocumentResult($documentResult)
	{
		$um = $documentResult->getUrlManager();
		if ($this->getTreeName())
		{
			$tn = $this->getDocumentServices()->getTreeManager()->getNodeByDocument($this);
			if ($tn)
			{
				$l = new TreeNodeLink($um, $tn, TreeNodeLink::MODE_LINK);
				$l->setRel('node');
				$documentResult->addLink($l);
			}
		}

		$model = $this->getDocumentModel();

		foreach ($model->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Property */
			$c = new PropertyConverter($this, $property, $um);
			$documentResult->setProperty($name, $c->getRestValue());
		}

		if ($this->getDocumentModel()->useCorrection())
		{
			/* @var $this \Change\Documents\Interfaces\Correction|\Change\Documents\AbstractDocument */
			$correction = $this->getCurrentCorrection();
			if ($correction)
			{
				$l = new DocumentActionLink($um, $this, 'correction');
				$documentResult->addAction($l);
			}
		}

		$this->updateRestDocumentResult($documentResult);

		$documentEvent = new \Change\Documents\Events\Event('updateRestResult', $this, array('restResult' => $documentResult,
			'urlManager' => $um));
		$this->getEventManager()->trigger($documentEvent);
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $documentResult
	 */
	protected function updateRestDocumentResult($documentResult)
	{

	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param Array
	 */
	public function populateRestDocumentLink($documentLink, $extraColumn)
	{
		$dm = $this->getDocumentManager();
		$eventManager = $this->getEventManager();
		if ($documentLink->getLCID())
		{
			$dm->pushLCID($documentLink->getLCID());
		}

		$model =  $this->getDocumentModel();

		$documentLink->setProperty($model->getProperty('creationDate'));
		$documentLink->setProperty($model->getProperty('modificationDate'));

		if ($this instanceof Editable)
		{
			$documentLink->setProperty($model->getProperty('label'));
			$documentLink->setProperty($model->getProperty('documentVersion'));
		}

		if ($this instanceof Publishable)
		{
			$documentLink->setProperty($model->getProperty('publicationStatus'));
		}
		elseif ($this instanceof Activable)
		{
			$documentLink->setProperty($model->getProperty('active'));
		}

		if ($this instanceof Localizable)
		{
			$documentLink->setProperty($model->getProperty('refLCID'));
			$documentLink->setProperty($model->getProperty('LCID'));
		}

		if ($this instanceof Correction)
		{
			/* @var $document AbstractDocument|Correction */
			if ($this->hasCorrection())
			{
				$l = new DocumentActionLink($documentLink->getUrlManager(), $this, 'correction');
				$documentLink->setProperty('actions', array($l));
			}
		}

		if (is_array($extraColumn) && count($extraColumn))
		{
			foreach ($extraColumn as $propertyName)
			{
				$property = $model->getProperty($propertyName);
				if ($property)
				{
					$documentLink->setProperty($property);
				}
			}
		}

		$this->updateRestDocumentLink($documentLink, $extraColumn);

		$documentEvent = new \Change\Documents\Events\Event('updateRestResult', $this,
			array('restResult' => $documentLink, 'extraColumn' => $extraColumn, 'urlManager' => $documentLink->getUrlManager()));
		$eventManager->trigger($documentEvent);

		if ($documentLink->getLCID())
		{
			$dm->popLCID();
		}
		return $this;
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param Array
	 */
	protected function updateRestDocumentLink($documentLink, $extraColumn)
	{

	}

	protected $ignoredPropertiesForRestEvents = array('model');

	/**
	 * Return false on error
	 * @param \Change\Http\Event $event
	 * @return $this|boolean
	 */
	public function populateDocumentFromRestEvent(\Change\Http\Event $event)
	{
		$data = $event->getRequest()->getPost()->toArray();
		foreach ($data as $name => $value)
		{
			if (!in_array($name, $this->ignoredPropertiesForRestEvents))
			{
				$result = $this->processRestData($name, $value, $event);
				if ($result === false)
				{
					return false;
				}
			}
		}
		$documentEvent = new \Change\Documents\Events\Event('populateDocumentFromRestEvent', $this,
			array('restEvent' => $event));
		$this->getEventManager()->trigger($documentEvent);

		return $event->getResult() instanceof ErrorResult ? false : $this;
	}

	/**
	 * Process the incoming REST data $name and set it to $value
	 *
	 * @param $name
	 * @param $value
	 * @param $event
	 * @return bool
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		$property = $this->getDocumentModel()->getProperty($name);
		if ($property)
		{
			if ($name == 'id' && intval($value) > 0 && $this->isNew())
			{
				$value = intval($value);
				$existingDocument = $this->getDocumentManager()->getDocumentInstance($value);
				if ($existingDocument)
				{
					$errorResult = new ErrorResult('DOCUMENT-ALREADY-EXIST', 'document already exist', HttpResponse::STATUS_CODE_409);
					$errorResult->setData(array('document-id' => $value));
					$errorResult->addDataValue('model-name', $this->getDocumentModelName());
					$event->setResult($errorResult);
					return false;
				}
				$this->initialize($value);
			}
			else
			{
				try
				{
					$c = new PropertyConverter($this, $property);
					$c->setPropertyValue($value);
				}
				catch (\Exception $e)
				{
					$errorResult = new ErrorResult('INVALID-VALUE-TYPE', 'Invalid property value type', HttpResponse::STATUS_CODE_409);
					$errorResult->setData(array('name' => $name, 'value' => $value, 'type' => $property->getType()));
					$errorResult->addDataValue('document-type', $property->getDocumentType());
					$event->setResult($errorResult);
					return false;
				}
			}
		}
		return true;
	}
}