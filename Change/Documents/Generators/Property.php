<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\Property
 */
class Property
{
	protected static $TYPES = array('String', 'Boolean', 'Integer', 'Float', 'Decimal',
				'Date', 'DateTime', 'LongString', 'XML', 'Lob', 'RichText', 'JSON', 'Object',
				'DocumentId', 'Document', 'DocumentArray');
	
	protected static $DEPRECATED_TYPE = array('Double' => 'Float', 'XHTMLFragment' => 'RichText', 'BBCode' => 'RichText');
	
	protected static $RESERVED_PROPERTY_NAMES = array('DbProvider', 'Path', 'DocumentModelName', 'PersistentModel', 'DocumentService', 'I18nInfo', 
		'OldValue', 'OldValues', 'S18s', 'Correctionofid', 'Correctionid', 'Meta');
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var string
	 */	
	protected $type;
	
	/**
	 * @var string
	 */
	protected $documentType;
	
	/**
	 * @var string description|property|none
	 */
	protected $indexed;
	
	/**
	 * @var string
	 */	
	protected $fromList;	
	
	/**
	 * @var boolean
	 */
	protected $cascadeDelete;

	/**
	 * @var string
	 */
	protected $defaultValue;
	
	/**
	 * @var boolean
	 */	
	protected $required;
	
	/**
	 * @var integer
	 */
	protected $minOccurs;
	
	/**
	 * @var integer
	 */	
	protected $maxOccurs;
	
	/**
	 * @var string
	 */
	protected $dbMapping;
		
	/**
	 * @var string
	 */	
	protected $dbSize;

	/**
	 * @var boolean
	 */
	protected $treeNode;

	/**
	 * @var boolean
	 */
	protected $localized;	
	
	/**
	 * @var boolean
	 */
	protected $inverse;
		
	/**
	 * @var array
	 */
	protected $constraintArray;
	
	/**
	 * Setted on Compilation 
	 * @var boolean
	 */
	protected $cmpOverride = false;
	

	/**
	 * @param DOMElement $xmlElement
	 */
	public function initialize($xmlElement)
	{
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;

			switch ($name)
			{
				case "name":
					if (in_array(ucfirst($value), self::$RESERVED_PROPERTY_NAMES))
					{
						throw new \Exception('Invalid property Name => ' . $value);
					}
					$this->name = $value;
					break;
				case "type":
					if (isset(self::$DEPRECATED_TYPE[$value])) {$value = self::$DEPRECATED_TYPE[$value];} //TODO Compatibility Check
					if (!in_array($value, static::$TYPES))
					{
						throw new \Exception('Invalid property Type => ' . $value);
					}
					else
					{
						$this->type = $value;
					}
					break;
				case "document-type":
					$this->documentType = $value;
					break;	
				case "indexed":
					if ($value == 'description' || $value == 'property')
					{
						$this->indexed = $value;
					}
					else
					{
						$this->indexed = 'none';
					}
					break;
				case "from-list":
					$this->fromList = $value;
					break;
				case "cascade-delete":
					$this->cascadeDelete = ($value === 'true');
					break;
				case "default-value":
					$this->defaultValue = $value;
					break;
				case "required":
					$this->required = ($value === 'true');
					break;
				case "min-occurs":
					$this->minOccurs = intval($value);
					break;
				case "max-occurs":
					$this->maxOccurs = intval($value);
					break;
				case "db-mapping":
					$this->dbMapping = $value;
					break;
				case "db-size":
					$this->dbSize = $value;
					break;
				case "tree-node":
					$this->treeNode = ($value === 'true');
					break;
				case "localized":
					$this->localized = ($value === 'true');
					break;
				case "inverse":
					$this->inverse = ($value === 'true');
					break;
				case "preserve-old-value": //DEPRECATED
					break;
				default:
					throw new \Exception('Invalid property attribute ' . $name . ' = ' . $value);
					break;
			}
		}
		
		if ($this->getName() === null)
		{
			throw new \Exception('Property Name can not be null');
		}

		foreach ($xmlElement->childNodes as $node)
		{
			/* @var $node \DOMElement */
			if ($node->nodeName == 'constraint')
			{
				if ($this->constraintArray === null)
				{
					$this->constraintArray = array();
				}
				$params = array();
				$name = null;
				foreach ($node->attributes as $attr) 
				{
					/* @var $attr \DOMAttr */
					if ($attr->name === 'name')
					{
						$name = $attr->value;
					}
					else
					{
						$v = $attr->value;
						if ($v === 'true') {$v = true;} elseif ($v === 'false') {$v = false;}
						$params[$attr->name] = $v;
					}
				}
				if ($name)
				{
					if ($this->constraintArray === null) {$this->constraintArray = array();}
					$this->constraintArray[$name] = $params;
				}
			}
			elseif ($node->nodeType == XML_ELEMENT_NODE)
			{
				throw new \Exception('Invalid property children node ' . $this->getName() . ' -> ' . $node->nodeName);
			}
		}
		
		$this->setDefaultConstraints();
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getDocumentType()
	{
		return $this->documentType;
	}

	/**
	 * @return string
	 */
	public function getIndexed()
	{
		return $this->indexed;
	}

	/**
	 * @return string
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
	 * @return string
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @return boolean
	 */
	public function getRequired()
	{
		return $this->required;
	}

	/**
	 * @return integer
	 */
	public function getMinOccurs()
	{
		return $this->minOccurs;
	}

	/**
	 * @return integer
	 */
	public function getMaxOccurs()
	{
		return $this->maxOccurs;
	}

	/**
	 * @return string
	 */
	public function getDbMapping()
	{
		return $this->dbMapping;
	}

	/**
	 * @return string
	 */
	public function getDbSize()
	{
		return $this->dbSize;
	}

	/**
	 * @return boolean
	 */
	public function getTreeNode()
	{
		return $this->treeNode;
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
	public function getInverse()
	{
		return $this->inverse;
	}

	/**
	 * @return array
	 */
	public function getConstraintArray()
	{
		return $this->constraintArray;
	}
	
	/**
	 * @param \Change\Documents\Generators\Property $property
	 */
	public function updateDefaultBy(\Change\Documents\Generators\Property $property)
	{
		if ($property->getType() !== null && $property->getType() != $this->getType())
		{
			throw new \Exception('Invalid property type redefinition');
		}
		
		if ($property->getDefaultValue() !== null) {$this->defaultValue = $property->getDefaultValue();}
		if ($property->getLocalized() !== null) {$this->localized = $property->getLocalized();}
		if (is_array($property->getConstraintArray())) {$this->constraintArray = $property->getConstraintArray();}
	}
	
	public function setDefaultConstraints()
	{
		if ($this->type == 'String' && $this->dbSize !== null)
		{
			$params = array('max' => intval($this->dbSize));
			if ($this->constraintArray === null) {$this->constraintArray = array();}
			if (!isset($this->constraintArray['maxSize']))
			{
				$this->constraintArray['maxSize'] = $params;
			}
		}
	}
	
	/**
	 * @param boolean $override
	 * @param string $baseType
	 */
	public function setOverride($override)
	{
		if ($override)
		{
			$this->cmpOverride = true;
		}
		else
		{
			$this->cmpOverride = false;
		}
	}
	
	/**
	 * @return boolean
	 */
	public function getOverride()
	{
		return $this->cmpOverride;
	}
	
	/**
	 * @param boolean $localized
	 */
	public function makeLocalized($localized)
	{
		$this->localized = $localized;
	}
	
	public function getDefaultPhpValue()
	{
		$val = $this->getDefaultValue();
		if ($val !== null)
		{
			if ($this->getType() === 'Boolean')
			{
				return $val === 'true';
			}
			if ($this->getType() === 'Integer' || $this->getType() === 'DocumentId')
			{
				return intval($val);
			}
			if ($this->getType() === 'Float' || $this->getType() === 'Decimal')
			{
				return floatval($val);
			}
		}
		return $val;
	}
	
	/**
	 * 
	 * @param \Change\Documents\Generators\Property[] $ancestors
	 */
	public function validate($ancestors)
	{
		if (count($ancestors))
		{
			$this->setOverride(true);		
			/* @var $ap \Change\Documents\Generators\Property */
			$ap = end($ancestors);
			if ($this->type !== null && $this->type !== $ap->getType())
			{
				throw new \Exception('Invalid inherited property Type:' . $this->type . ' -> ' . $ap->getType());
			}
			$this->type = $ap->getType();
		}
		else
		{
			$this->setOverride(false);
		}
		
		if ($this->getType() === null)
		{
			throw new \Exception('No type defined on Property: ' .  $this->name);
		}
		
		$hasRelation = ($this->getType() === 'Document' || $this->getType() === 'DocumentArray');
		
		if (!$hasRelation && $this->getTreeNode() !== null)
		{
			throw new \Exception('Invalid TreeNode property attribute on :' . $this->name);
		}
		
		if (!$hasRelation && $this->getInverse() !== null)
		{
			throw new \Exception('Invalid Inverse property attribute on :' . $this->name);
		}
		
		if ($hasRelation && $this->getLocalized() !== null)
		{
			throw new \Exception('Invalid localized property attribute on :' . $this->name);
		}
		
		if ($this->getLocalized() === false)
		{
			foreach ($ancestors as $ap)
			{
				/* @var $ap \Change\Documents\Generators\Property */
				if ($ap->getLocalized())
				{
					throw new \Exception('Invalid localized property value on :' . $this->name);
				}
			}
		}
		
		if ($hasRelation && $this->getInverse() && $this->getDocumentType() === null)
		{
			foreach (array_reverse($ancestors) as $ap)
			{
				/* @var $ap \Change\Documents\Generators\Property */
				if ($ap->getDocumentType())
				{
					$this->documentType = $ap->getDocumentType();
					break;
				}
			}
			if ($this->getDocumentType() === null)
			{
				throw new \Exception('Invalid inverse Document type property attribute on :' . $this->name);
			}
		}
		
		if ($this->getType() !== 'String' && $this->getType() !== 'Decimal' && $this->getDbSize() !== null)
		{
			throw new \Exception('Invalid db-size property attribute on :' . $this->name);
		}
	}

	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public static function getNewCorrectionIdProperty()
	{
		$property = new static();
		$property->name = 'correctionid';
		$property->type = 'Integer';
		return $property;
	}
	
	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public static function getNewCorrectionOfIdProperty()
	{
		$property = new static();
		$property->name = 'correctionofid';
		$property->type = 'Integer';
		return $property;
	}
	
	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public static function getNewS18sProperty()
	{
		$property = new static();
		$property->name = 's18s';
		$property->type = 'Lob';
		return $property;
	}
	
	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public static function getNamedProperty($name)
	{
		$property = new static();
		$property->name = $name;
		return $property;
	}
}
