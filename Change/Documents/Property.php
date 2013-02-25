<?php
namespace Change\Documents;

/**
 * @api
 * @name \Change\Documents\Property
 */
class Property
{
	const TYPE_BOOLEAN = 'Boolean';
	const TYPE_INTEGER = 'Integer';
	const TYPE_FLOAT = 'Float';
	const TYPE_DECIMAL = 'Decimal';
	
	const TYPE_DATETIME = 'DateTime';
	const TYPE_DATE = 'Date';
	
	const TYPE_STRING = 'String';
	
	const TYPE_LONGSTRING = 'LongString';
	const TYPE_XML = 'XML';
	
	const TYPE_RICHTEXT = 'RichText';
	const TYPE_JSON = 'JSON';
	
	const TYPE_LOB = 'Lob';
	const TYPE_OBJECT = 'Object';
	
	const TYPE_DOCUMENTID = 'DocumentId';
	const TYPE_DOCUMENT = 'Document';
	const TYPE_DOCUMENTARRAY = 'DocumentArray';
	
	
	protected $name;
	protected $type = self::TYPE_STRING;
	protected $documentType = null;
	
	protected $required = false;
	protected $minOccurs = 0;
	protected $maxOccurs = 1;

	protected $cascadeDelete = false;
	
	protected $defaultValue;
	protected $constraintArray;
	protected $localized = false;
	protected $hasCorrection = false;
	protected $indexed = 'none'; //none, property, description
	
	/**
	 * @param string $name
	 * @param string $type
	 */
	function __construct($name, $type = null)
	{
		$this->name = $name;
		if ($type != null)
		{
			$this->setType($type);
		}
	}

	/**
	 * @api
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @api
	 * @return string|NULL
	 */
	public function getDocumentType()
	{
		return $this->documentType;
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * @return integer
	 */
	public function getMinOccurs()
	{
		return max($this->minOccurs, $this->isRequired() ? 1 : 0);
	}

	/**
	 * @return integer
	 */
	public function getMaxOccurs()
	{
		return $this->maxOccurs;
	}

	/**
	 * @return boolean
	 */
	public function getCascadeDelete()
	{
		return $this->cascadeDelete;
	}

	/**
	 * @return boolean
	 */
	public function getLocalized()
	{
		return $this->localized;
	}
	
	/**
	 * @return boolean
	 */
	public function getHasCorrection()
	{
		return $this->hasCorrection;
	}
	
	/**
	 * @return string [none], property, description
	 */
	public function getIndexed()
	{
		return $this->indexed;
	}
	
	/**
	 * @return boolean
	 */
	public function isIndexed()
	{
		return $this->indexed != 'none';
	}


	/**
	 * Indicates whether the document property accepts all types of document.
	 *
	 * @return boolean
	 */
	public function acceptAllTypes()
	{
		if ($this->isDocument())
		{
			return $this->documentType === null;
		}
		return false;
	}
	
	/**
	 * Indicates whether the property is a string or not.
	 *
	 * @return boolean
	 */
	public function isString()
	{
		return $this->type === self::TYPE_STRING;
	}

	/**
	 * Indicates whether the property is a long string or not.
	 *
	 * @return boolean
	 */
	public function isLob()
	{
		switch ($this->type)
		{
			case self::TYPE_LOB:
			case self::TYPE_XML:
			case self::TYPE_JSON:
			case self::TYPE_OBJECT:
			case self::TYPE_LONGSTRING:
			case self::TYPE_RICHTEXT:
				return true;
			default:
				return false;
		}
	}
	
	/**
	 * Indicates whether the property is a number.
	 *
	 * @return boolean
	 */
	public function isNumeric()
	{
		switch ($this->type)
		{
			case self::TYPE_INTEGER:
			case self::TYPE_DECIMAL:
			case self::TYPE_DOUBLE:
				return true;
			default:
				return false;
		}
	}
	
	/**
	 * Indicates whether the property is a document or not.
	 *
	 * @return boolean
	 */
	public function isDocument()
	{
		return ($this->type === self::TYPE_DOCUMENT || $this->type === self::TYPE_DOCUMENTARRAY);;
	}

	/**
	 * @param integer $value
	 * @return \Change\Documents\Property
	 */
	public function setMinOccurs($value)
	{
		$this->minOccurs = intval($value);
		return $this;
	}

	/**
	 * @param integer $value
	 * @return \Change\Documents\Property
	 */
	public function setMaxOccurs($value)
	{
		$this->maxOccurs = intval($value);
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function isRequired()
	{
		return $this->getRequired();
	}
	
	/**
	 * @return boolean
	 */
	public function getRequired()
	{
		return $this->required;
	}
	
	/**
	 * @param boolean $value
	 * @return \Change\Documents\Property
	 */
	public function setRequired($value)
	{
		$this->required = ($value == true);
		return $this;
	}
	
	/**
	 * Indicates whether the property is multi-valued or not.
	 *
	 * @return boolean
	 */
	public function isArray()
	{
		return $this->maxOccurs != 1;
	}

	/**
	 * Indicates whether the property is unique or not.
	 *
	 * @return boolean
	 */
	public function isUnique()
	{
		return $this->maxOccurs == 1;
	}

	/* Information de présentation */

	/**
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param mixed $value
	 * @return \Change\Documents\Property
	 */
	public function setDefaultValue($value)
	{
		$this->defaultValue = $value;
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function hasConstraints()
	{
		return is_array($this->constraintArray) && count($this->constraintArray);
	}
	
	/**	
	 * Returns the constraints defined for the property.
	 *
	 * @return array
	 */
	public function getConstraintArray()
	{
		return $this->constraintArray;
	}
	
	/**
	 * @param array $constraintArray
	 * @return \Change\Documents\Property
	 */
	public function setConstraintArray($constraintArray)
	{
		if (is_array($constraintArray))
		{
			if (is_array($this->constraintArray))
			{
				$this->constraintArray = array_merge($this->constraintArray, $constraintArray);
			}
			else
			{
				$this->constraintArray = $constraintArray;
			}
			
		}
		else
		{
			$this->constraintArray = null;
		}
		return $this;
	}

	/**
	 * @return integer or -1
	 */
	public function getMaxSize()
	{
		if ($this->isString() && is_array($this->constraintArray) && isset($this->constraintArray['maxSize']))
		{
			return intval($this->constraintArray['maxSize']['parameter']);
		}
		return -1;
	}
	
	/**
	 * @param string $name
	 * @return \Change\Documents\Property
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param string $type
	 * @return \Change\Documents\Property
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}
		
	/**
	 * @param string $documentType
	 * @return \Change\Documents\Property
	 */
	public function setDocumentType($documentType)
	{
		$this->documentType = $documentType;
		return $this;
	}
	
	/**
	 * @param boolean $cascadeDelete
	 * @return \Change\Documents\Property
	 */
	public function setCascadeDelete($cascadeDelete)
	{
		$this->cascadeDelete = $cascadeDelete;
		return $this;
	}

	/**
	 * @param string $indexed
	 * @return \Change\Documents\Property
	 */
	public function setIndexed($indexed)
	{
		$this->indexed = $indexed;
		return $this;
	}

	/**
	 * @param boolean $bool
	 * @return \Change\Documents\Property
	 */
	public function setLocalized($bool)
	{
		$this->localized = $bool ? true : false;
		return $this;
	}
	
	/**
	 * @param boolean $bool
	 * @return \Change\Documents\Property
	 */
	public function setHasCorrection($bool)
	{
		$this->hasCorrection = $bool ? true : false;
		return $this;
	}
	
	/**
	 * @param mixed $treeNode
	 * @return \Change\Documents\Property
	 */
	public function setTreeNode($treeNode)
	{
		$this->treeNode = $treeNode;
		return $this;
	}
	
	/**
	 * @api
	 * @return \Change\Documents\Property
	 */
	public function normalize()
	{
		if ($this->type !== self::TYPE_DOCUMENTARRAY)
		{
			$this->setMaxOccurs(1);
		}
		elseif ($this->maxOccurs <= 1)
		{
			$this->setMaxOccurs(-1);
		}
	
		if ($this->documentType !== null && !$this->isDocument() && $this->type !== self::TYPE_DOCUMENTID)
		{
			$this->documentType = null;
		}
		return $this;
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\AbstractLocalizedDocument $document
	 * @return mixed
	 */
	public function getValue($document)
	{
		if ($this->name === 'model')
		{
			$getter = 'getDocumentModelName';
		}
		else
		{
			$getter = 'get' . ucfirst($this->name);
		}
		return call_user_func(array($document, $getter));
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return mixed
	 */
	public function getOldValue($document)
	{
		if ($this->name === 'id')
		{
			$getter = 'getId';
		}
		elseif ($this->name === 'model')
		{
			$getter = 'getDocumentModelName';
		}
		else
		{
			$getter = 'get' . ucfirst($this->name).'OldValue';
		}
		return call_user_func(array($document, $getter));
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\AbstractLocalizedDocument $document
	 * @param mixed $value
	 */
	public function setValue($document, $value)
	{
		if ($this->name !== 'id' && $this->name !== 'model')
		{
			$setter = 'set' . ucfirst($this->name);
			call_user_func(array($document, $setter), $value);
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->name . '('.$this->type.')';
	}
}