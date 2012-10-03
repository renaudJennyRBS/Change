<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\Property
 */
class Property
{
	protected static $TYPES = array('String', 'Boolean', 'Integer', 'Double', 'Decimal',
				'DateTime', 'LongString', 'XHTMLFragment', 'Lob', 'BBCode', 'JSON', 'Object',
				'DocumentId', 'Document', 'DocumentArray');
	
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
	 * @var boolean
	 */	
	protected $preserveOldValue;
	
	/**
	 * @var array
	 */
	protected $constraintArray;
	

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
					$this->name = $value;
					break;
				case "type":
					if (!in_array($value, static::$TYPES))
					{
						throw new \Exception('Invalid property Type ' . $name . ' => ' . $value);
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
				case "preserve-old-value":
					$this->preserveOldValue = ($value === 'true');
					break;
				default:
					throw new \Exception('Invalid property attribute ' . $name . ' = ' . $value);
					break;
			}
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
	 * @return boolean
	 */
	public function getPreserveOldValue()
	{
		return $this->preserveOldValue;
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
}
