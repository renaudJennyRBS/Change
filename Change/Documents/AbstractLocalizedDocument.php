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
	private $persistentState = AbstractDocument::STATE_NEW;

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
	 * @param integer $persistentState \Change\Documents\AbstractDocument::STATE_*
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
	 * Return \Change\Documents\AbstractDocument::STATE_*
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
		return $this->getPersistentState() === AbstractDocument::STATE_NEW;
	}

	/**
	 * @param integer $newValue \Change\Documents\AbstractDocument::STATE_*
	 * @return integer
	 */
	public final function setPersistentState($newValue)
	{
		$oldState = $this->persistentState;
		switch ($newValue)
		{
			case AbstractDocument::STATE_LOADED:
				$this->clearModifiedProperties();
				$this->persistentState = $newValue;
				break;
			case AbstractDocument::STATE_NEW:
			case AbstractDocument::STATE_LOADING:
			case AbstractDocument::STATE_DELETED:
			case AbstractDocument::STATE_DELETING:
			case AbstractDocument::STATE_SAVING:
				$this->persistentState = $newValue;
				break;
		}
		return $oldState;
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

			default:
				return $inputValue === null ? $inputValue : strval($inputValue);
		}
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