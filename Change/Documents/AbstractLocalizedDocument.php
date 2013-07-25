<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractLocalizedDocument
 * @api
 */
abstract class AbstractLocalizedDocument implements \Serializable
{
	/**
	 * @var AbstractModel
	 */
	protected $documentModel;

	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var integer
	 */
	private $persistentState = DocumentManager::STATE_NEW;

	/**
	 * @var array
	 */
	protected $modifiedProperties = array();

	/**
	 * @param AbstractModel $documentModel
	 */
	function __construct(AbstractModel $documentModel)
	{
		$this->setDocumentModel($documentModel);
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
	public function initialize($id, $lcid, $persistentState = null)
	{
		$this->id = intval($id);
		$this->setLCID($lcid);
		if ($persistentState !== null)
		{
			$this->persistentState = $this->setPersistentState($persistentState);
		}
	}

	/**
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	public final function setDocumentModel(\Change\Documents\AbstractModel $documentModel)
	{
		$this->documentModel = $documentModel;
	}

	/**
	 * @return \Change\Documents\AbstractModel
	 */
	public final function getDocumentModel()
	{
		return $this->documentModel;
	}


	/**
	 * \Change\Documents\DocumentManager::STATE_*
	 * @return integer
	 */
	public final function getPersistentState()
	{
		return $this->persistentState;
	}

	/**
	 * @return boolean
	 */
	public function isNew()
	{
		return $this->getPersistentState() === DocumentManager::STATE_NEW;
	}

	/**
	 * \Change\Documents\DocumentManager::STATE_*
	 * @param integer $newValue
	 * @return integer
	 */
	public final function setPersistentState($newValue)
	{
		$oldState = $this->persistentState;
		switch ($newValue)
		{
			case DocumentManager::STATE_LOADED:
				$this->clearModifiedProperties();
			case DocumentManager::STATE_NEW:
			case DocumentManager::STATE_LOADING:
			case DocumentManager::STATE_DELETED:
			case DocumentManager::STATE_DELETING:
			case DocumentManager::STATE_SAVING:
				$this->persistentState = $newValue;
				break;
		}
		return $oldState;
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
	public function hasModifiedProperties()
	{
		return count($this->getModifiedPropertyNames()) !== 0;
	}

	/**
	 * @return void
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
	public final function isPropertyModified($propertyName)
	{
		return in_array($propertyName, $this->getModifiedPropertyNames());
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
	public final function getOldPropertyValue($propertyName)
	{
		if (array_key_exists($propertyName, $this->modifiedProperties))
		{
			return $this->modifiedProperties[$propertyName];
		}
		return null;
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