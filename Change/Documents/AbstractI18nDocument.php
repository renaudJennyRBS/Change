<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractI18nDocument
 */
abstract class AbstractI18nDocument
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
		$this->setDocumentContext($manager);
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $manager
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Documents\AbstractService $service
	 */
	public function setDocumentContext(\Change\Documents\DocumentManager $manager)
	{
		$this->documentManager = $manager;
	}
	
	/**
	 * @return string[]
	 */
	public function __sleep()
	{
		return array("\0".__CLASS__."\0id");
	}
	
	/**
	 */
	public function __wakeup()
	{
		\Change\Application::getInstance()->getDocumentServices()->getDocumentManager()->postI18nUnserialze($this);
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
			case DocumentManager::STATE_DELETING:
			case DocumentManager::STATE_DELETED:
			case DocumentManager::STATE_SAVING:
				$this->persistentState = $newValue;
				break;
		}
		return $oldState;
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
	 * @return array<string => mixed>
	 */
	public function getOldPropertyValues()
	{
		return $this->modifiedProperties;
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
	
	// Generic Method
	
	/**
	 * @return string
	 */
	abstract public function getLCID();
	
	/**
	 * @param string $LCID
	 */
	abstract public function setLCID($LCID);
	
	/**
	 * @return \DateTime
	 */
	abstract public function getCreationDate();
	
	/**
	  * @param \DateTime $creationDate
	*/
	abstract public function setCreationDate($creationDate);
	
	/**
	 * @return \DateTime
	 */
	abstract public function getModificationDate();
	
	/**
	 * @param \DateTime $modificationDate
	*/
	abstract public function setModificationDate($modificationDate);
	
	/**
	 * @return \DateTime|null
	 */
	abstract public function getDeletedDate();
	
	/**
	 * @param \DateTime|null $deletedDate
	 */
	abstract public function setDeletedDate($deletedDate);
}