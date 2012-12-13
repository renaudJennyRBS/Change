<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractDocument
 */
abstract class AbstractDocument
{	
	const PERSISTENTSTATE_NEW = 0;
	
	const PERSISTENTSTATE_INITIALIZED = 2;
	
	const PERSISTENTSTATE_LOADED = 3;
	const PERSISTENTSTATE_MODIFIED = 4;
	
	const PERSISTENTSTATE_DELETED = 5;
	
	/**
	 * @var integer
	 */
	private $persistentState;

	/**
	 * @var integer
	 */
	private $id = 0;
	
	/**
	 * @var string
	 */
	private $documentModelName;
	
	/**
	 * @var integer
	 */
	private $treeId;
	
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
	 * @var array
	 */
	private $propertiesErrors;
	
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;
	
	/**
	 * @var \Change\Documents\AbstractModel
	 */
	protected $documentModel;
	
	/**
	 * @var \Change\Documents\AbstractService
	 */
	protected $documentService;

	/**
	 * @param \Change\Documents\DocumentManager $manager
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Documents\AbstractService $service
	 */
	public function __construct(\Change\Documents\DocumentManager $manager, \Change\Documents\AbstractModel $model, \Change\Documents\AbstractService $service)
	{
		$this->setDocumentContext($manager, $model, $service);
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $manager
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Documents\AbstractService $service
	 */
	public function setDocumentContext(\Change\Documents\DocumentManager $manager, \Change\Documents\AbstractModel $model, \Change\Documents\AbstractService $service)
	{
		$this->documentManager = $manager;
		$this->documentModel = $model;
		$this->documentModelName = $model->getName();
		$this->documentService = $service;
	}
	
	/**
	 * @return string[]
	 */
	public function __sleep()
	{
		return array("\0".__CLASS__."\0id", "\0".__CLASS__."\0documentModelName");
	}
	
	/**
	 */
	public function __wakeup()
	{
		\Change\Application::getInstance()->getDocumentServices()->getDocumentManager()->postUnserialze($this);
	}

	/**
	 * @api
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
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
	 * @api
	 * @return \Change\Documents\AbstractService
	 */
	public function getDocumentService()
	{
		return $this->documentService;
	}
	
	/**
	 * @param integer $id
	 * @param integer $persistentState
	 * @param integer|null $treeId
	 */
	public function initialize($id, $persistentState, $treeId)
	{
		$this->id = intval($id);
		$this->setPersistentState($persistentState);
		$this->setTreeId($treeId);
	}

	/**
	 * @param integer|null $treeId
	 */
	public function setTreeId($treeId)
	{
		$this->treeId = ($treeId !== null) ? intval($treeId) : null;
	}
	
	/**
	 * @api
	 * @return integer|null
	 */
	public function getTreeId()
	{
		return $this->treeId;
	}

	/**
	 * Set the default properties value for new document
	 */
	protected function setDefaultValues()
	{
		$this->modifiedProperties = array();
		$this->persistentState = self::PERSISTENTSTATE_NEW;
	}
	
	/**
	 * @return integer
	 */
	public function getPersistentState()
	{
		return $this->persistentState;
	}

	/**
	 * @param integer $newValue
	 */
	public function setPersistentState($newValue)
	{
		$oldState = $this->persistentState;
		switch ($newValue) 
		{
			case self::PERSISTENTSTATE_NEW:
			case self::PERSISTENTSTATE_INITIALIZED:
			case self::PERSISTENTSTATE_LOADED:
			case self::PERSISTENTSTATE_MODIFIED:
			case self::PERSISTENTSTATE_DELETED:
				$this->persistentState = intval($newValue);
				break;
		}
		return $oldState;
	}
	
	/**
	 * @return boolean
	 */
	public function persistentStateIsNew()
	{
		return $this->persistentState === self::PERSISTENTSTATE_NEW;
	}

	/**
	 * @return array
	 */
	public function getDocumentProperties()
	{
		$propertyBag = array();
		$propertyBag['id'] = $this->id;
		$propertyBag['model'] = $this->getDocumentModelName();
		return $propertyBag;
	}

	/**
	 * @param array $propertyBag
	 */
	public function setDocumentProperties($propertyBag)
	{
		if (array_key_exists('id', $propertyBag))
		{
			$this->id = intval($propertyBag['id']);
		}
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
	 * @return void
	 */
	protected final function loadDocument()
	{
		$this->documentManager->loadDocument($this);
	}
	
	protected function checkLoaded()
	{
		if ($this->persistentState === self::PERSISTENTSTATE_INITIALIZED)
		{
			$this->loadDocument();
		}
	}

	/**
	 * @api
	 */
	public final function save()
	{
		$this->documentService->save($this);
	}
	
	/**
	 * Overrided by compiled document class
	 */
	protected function validateProperties()
	{
		
	}
	
	/**
	 * @api
	 * validate document and return boolean result
	 * @return boolean
	 */
	public function isValid()
	{
		$this->propertiesErrors = null;
		$this->validateProperties();
		return !$this->hasPropertiesErrors();
	}
	
	/**
	 * @api
	 * @return array<propertyName => string[]>
	 */
	public function getPropertiesErrors()
	{
		if ($this->hasPropertiesErrors())
		{
			return $this->propertiesErrors;
		}
		return array();
	}
	
	/**
	 * @return boolean
	 */
	protected function hasPropertiesErrors()
	{
		return is_array($this->propertiesErrors) && count($this->propertiesErrors);
	}
	
	/**
	 * @param string $propertyName
	 * @param string[] $errors
	 */
	protected function addPropertyErrors($propertyName, $errors)
	{		
		if (is_array($errors) && count($errors))
		{
			if (!$this->hasPropertiesErrors())
			{
				$this->propertiesErrors = array($propertyName => $errors);
			}
			elseif (isset($this->propertiesErrors[$propertyName]))
			{
				$this->propertiesErrors[$propertyName] = array_merge($this->propertiesErrors[$propertyName], $errors);
			}
			else
			{
				$this->propertiesErrors[$propertyName] = $errors;
			}
		}
	}	
	
	/**
	 * @param string $propertyName
	 */
	protected function clearPropertyErrors($propertyName = null)
	{
		if ($propertyName === null)
		{
			$this->propertiesErrors = null;
		}
		elseif (is_array($this->propertiesErrors) && isset($this->propertiesErrors[$propertyName]))
		{
			unset($this->propertiesErrors[$propertyName]);
		}
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
	 * @return boolean
	 */
	public function isPropertyModified($propertyName)
	{
		return array_key_exists($propertyName, $this->modifiedProperties);
	}
	
	/**
	 * @param string $propertyName
	 */
	protected function removeOldPropertyValue($propertyName)
	{
		if (array_key_exists($propertyName, $this->modifiedProperties))
		{
			unset($this->modifiedProperties[$propertyName]);
		}
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
	 * @return mixed
	 */
	public function getOldPropertyValue($propertyName)
	{
		if (array_key_exists($propertyName, $this->modifiedProperties))
		{
			return $this->modifiedProperties[$propertyName];
		}
		return null;
	}

	/**
	 * @api
	 * @return array<string => mixed>
	 */
	public function getOldPropertyValues()
	{
		return $this->modifiedProperties;
	}

	/**
	 * Called everytime a property has changed.
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
			$this->documentManager->saveMetas($this, $this->metas);
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
	 * 
	 */
	protected function checkMetasLoaded()
	{
		if ($this->metas === null)
		{
			$this->metas = $this->documentManager->loadMetas($this);
			$this->modifiedMetas = false;
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
		elseif ($this->metas[$name] != $value)
		{
			$this->metas[$name] = $value;
			$this->modifiedMetas = true;
		}
	}
	
	// Generic Method
	
	/**
	 * @return string
	 */
	abstract public function getCreationDate();
	
	/**
	 * @param string $creationDate
	 */
	abstract public function setCreationDate($creationDate);
	
	
	/**
	 * @return string
	 */
	abstract public function getModificationDate();
	
	/**
	 * @param string $modificationDate
	 */
	abstract public function setModificationDate($modificationDate);
	
	/**
	 * @return string
	*/
	abstract public function getDeletedDate();
	
	/**
	 * @param string $deletedDate
	 */
	abstract public function setDeletedDate($deletedDate);
}