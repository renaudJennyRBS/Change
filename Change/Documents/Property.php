<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\Property
 */
class Property
{
	protected $name;
	protected $type = \Change\Documents\AbstractDocument::PROPERTYTYPE_STRING;
	protected $documentType = null;
	protected $relationName = null;
	protected $required = false;
	protected $minOccurs = 0;
	protected $maxOccurs = 1;
	protected $dbMapping;

	protected $cascadeDelete = false;
	protected $treeNode = false;
	protected $isDocument = false;
	protected $defaultValue;
	protected $constraintArray;
	protected $localized = false;
	protected $indexed = 'none'; //none, property, description
	protected $fromList;

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
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @return string|NULL
	 */
	public function getDocumentType()
	{
		return $this->documentType;
	}
	
	/**
	 * @return string|NULL
	 */
	public function getRelationName()
	{
		return $this->relationName;
	}	
	
	/**
	 * @return boolean
	 */
	public function getTreeNode()
	{
		return $this->treeNode;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * Returns the name of the field that represents this property into the
	 * database table.
	 *
	 * @return string
	 */
	public function getDbMapping()
	{
		return $this->dbMapping;
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
	 * @return string | null
	 */
	public function getFromList()
	{
		return $this->fromList;
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
	 * @return f_persistentdocument_PersistentDocumentModel|NULL
	 */
	public function getPersistentModel()
	{
		if ($this->documentType)
		{
			//TODO Old class Usage
			return \f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->documentType);
		}
		return null;
	}
		

	/**
	 * Returns the type of subdocuments with the slash replaced by an underscore
	 * for use on the backoffice side.
	 *
	 * @return string|NULL
	 */
	public function getTypeForBackofficeWidgets()
	{
		if ($this->documentType)
		{
			//TODO Old class Usage
			return \f_persistentdocument_PersistentDocumentModel::convertModelNameToBackoffice($this->documentType);
		}
		return null;
	}

	/**
	 * Indicates whether the document property accepts documents of type $type.
	 *
	 * @return boolean
	 */
	public function acceptType($type)
	{
		if ($this->documentType)
		{
			//TODO Old class Usage
			return \f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($type)->isModelCompatible($this->documentType);
		}
		return false;
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
			//TODO Old class Usage
			return $this->documentType === \f_persistentdocument_PersistentDocumentModel::BASE_MODEL;
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
		return $this->type === \Change\Documents\AbstractDocument::PROPERTYTYPE_STRING;
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
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_LOB:
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_XML:
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_JSON:
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_OBJECT:
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_LONGSTRING:
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_RICHTEXT:
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
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_INTEGER:
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DECIMAL:
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DOUBLE:
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
		return $this->isDocument;
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
	 * @return string
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param string $value
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
		$this->constraintArray = is_array($constraintArray) ? $constraintArray : null;
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
		$this->isDocument = ($this->type === \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENT || 
			$this->type === \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENTARRAY);
		
		if ($this->maxOccurs === 1 && $this->type === \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENTARRAY)
		{
			$this->setMaxOccurs(-1);
		}
		if ($this->documentType === null && ($this->isDocument || $this->type === \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENTID))
		{
			//TODO Old class Usage
			$this->setDocumentType(\f_persistentdocument_PersistentDocumentModel::BASE_MODEL);
		}	
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
	 * @param string $relationName
	 * @return \Change\Documents\Property
	 */
	public function setRelationName($relationName)
	{
		$this->relationName = $relationName;
		return $this;
	}

	/**
	 * @param string $dbMapping
	 * @return \Change\Documents\Property
	 */
	public function setDbMapping($dbMapping)
	{
		$this->dbMapping = $dbMapping;
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
	 * @param string $fromList
	 * @return \Change\Documents\Property
	 */
	public function setFromList($fromList)
	{
		$this->fromList = $fromList;
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
	 * @param mixed $treeNode
	 * @return \Change\Documents\Property
	 */
	public function setTreeNode($treeNode)
	{
		$this->treeNode = $treeNode;
		return $this;
	}
}