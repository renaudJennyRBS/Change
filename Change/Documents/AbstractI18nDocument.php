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
	private $persistentState;

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
	 * @param integer $persistentState
	 */
	public function initialize($id, $lcid, $persistentState)
	{
		$this->id = intval($id);
		$this->setLCID($lcid);
		$this->persistentState = $this->setPersistentState($persistentState);
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
			case AbstractDocument::PERSISTENTSTATE_NEW:
			case AbstractDocument::PERSISTENTSTATE_LOADED:
			case AbstractDocument::PERSISTENTSTATE_MODIFIED:
			case AbstractDocument::PERSISTENTSTATE_DELETED:
				$this->persistentState = intval($newValue);
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
	 * @return string
	 */
	abstract public function getLCID();
	
	/**
	 * @param string $LCID
	*/
	abstract public function setLCID($LCID);
			
	/**
	 * @return boolean
	 */
	public function persistentStateIsNew()
	{
		return $this->persistentState === AbstractDocument::PERSISTENTSTATE_NEW;
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
	 * @return void
	 */
	public function setDefaultValues()
	{
		$this->modifiedProperties = array();
		$this->persistentState = AbstractDocument::PERSISTENTSTATE_NEW;
	}
	
	 /**
	 * @internal For framework internal usage only
	 * @param array<string, mixed> $propertyBag
	 */
	public function setDocumentProperties($propertyBag)
	{
		if (array_key_exists('id', $propertyBag))
		{
			$this->id = intval($propertyBag['id']);
		}
	}
	
	/**
	 * @return array<String, mixed>
	 */
	public function getDocumentProperties()
	{
		$propertyBag = array();
		$propertyBag['id'] = $this->id;
		
		return $propertyBag;
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