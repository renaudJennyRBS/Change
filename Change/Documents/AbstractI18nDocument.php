<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractI18nDocument
 * @api
 */
abstract class AbstractI18nDocument implements \Serializable
{
	/**
	 * @var integer
	 */
	private $id;
	
	/**
	 * @var integer
	 */
	private $persistentState = DocumentManager::STATE_NEW;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;
	
	/**
	 * @var array
	 */
	protected $modifiedProperties = array();

	/**
	 * @param \Change\Documents\DocumentManager $manager
	 */
	public function __construct(\Change\Documents\DocumentManager $manager)
	{
		$this->setDocumentManager($manager);
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $manager
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $manager)
	{
		$this->documentManager = $manager;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
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
	 * @param integer $id
	 * @param string $lcid
	 * @param integer $persistentState DocumentManager::STATE_*
	 */
	public function initialize($id, $lcid, $persistentState)
	{
		$this->id = intval($id);
		$this->setLCID($lcid);
		$this->persistentState = $this->setPersistentState($persistentState);
	}
	
	/**
	 * @return integer DocumentManager::STATE_*
	 */
	public function getPersistentState()
	{
		return $this->persistentState;
	}
	
	/**
	 * @param integer $newValue DocumentManager::STATE_*
	 */
	public function setPersistentState($newValue)
	{
		$oldState = $this->persistentState;
		switch ($newValue)
		{
			case DocumentManager::STATE_LOADED:
				$this->clearModifiedProperties();
			case DocumentManager::STATE_NEW:
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
	 * @param array $modifiedProperties
	 */
	public function setModifiedProperties($modifiedProperties = array())
	{
		$this->modifiedProperties = $modifiedProperties;
		$this->isModified = count($modifiedProperties) > 0;
	}
	
	/**
	 * @return array
	 */
	public function getModifiedProperties()
	{
		return $this->modifiedProperties;
	}
	
	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
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
	public function hasModifiedProperties()
	{
		return count($this->modifiedProperties) > 0;
	}
	
	/**
	 * @api
	 */
	protected function clearModifiedProperties()
	{
		$this->modifiedProperties = array();
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
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	public function setDefaultValues(\Change\Documents\AbstractModel $documentModel)
	{
		$this->persistentState = DocumentManager::STATE_NEW;
		foreach ($documentModel->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getLocalized() && $property->getDefaultValue() !== null)
			{
				$property->setValue($this, $property->getDefaultValue());
			}
		}
		$this->clearModifiedProperties();
	}
	
	/**
	 * @api
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	public function reset(\Change\Documents\AbstractModel $documentModel)
	{
		$this->modifiedProperties = array();
		if ($this->persistentState === DocumentManager::STATE_LOADED)
		{
			$this->persistentState = DocumentManager::STATE_INITIALIZED;
		}
		elseif($this->persistentState === DocumentManager::STATE_NEW)
		{
			$this->setDefaultValues($documentModel);
		}
	}
	
	// Generic Method
	
	/**
	 * @api
	 * @return string
	 */
	abstract public function getLCID();
	
	/**
	 * @api
	 * @param string $LCID
	 */
	abstract public function setLCID($LCID);
	
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