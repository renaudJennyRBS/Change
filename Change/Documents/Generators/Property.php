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
	
	protected static $RESERVED_PROPERTY_NAMES = array('id', 'model', 'treename', 'meta', 'metas', 'volcid', 'lcid',
			'creationdate', 'modificationdate',
			'authorname', 'authorid', 'documentversion',
			'publicationstatus', 'startpublication', 'endpublication',
			'correctionofid', 'versionofid');
	
	protected static $RESERVED_PROPERTY_METHODS = array('get{Name}', 'set{Name}', 'get{Name}OldValue', 
		'get{Name}DOMDocument', 'set{Name}DOMDocument', 'getDecoded{Name}', 'get{Name}Instance', 
		'get{Name}OldValueId', 'get{Name}Id', 
		'get{Name}OldValueIds', 'add{Name}', 'set{Name}AtIndex', 'remove{Name}', 'remove{Name}ByIndex', 
		'removeAll{Name}', 'get{Name}ByIndex', 'get{Name}Ids', 'getIndexof{Name}');
	
	/**
	 * @var \Change\Documents\Generators\Property
	 */
	protected $parent;
	
	/**
	 * @var \Change\Documents\Generators\Model
	 */
	protected $model;
		
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
	 * @var boolean
	 */
	protected $localized;
		
	/**
	 * @var array
	 */
	protected $constraintArray;
	
	/**
	 * @return string[]
	 */
	public static function getReservedPropertyNames()
	{
		return static::$RESERVED_PROPERTY_NAMES;
	}
	
	/**
	 * @return string[]
	 */
	public static function getValidPropertyTypes()
	{
		return static::$TYPES;
	}
	

	public function __construct(\Change\Documents\Generators\Model $model, $name = null, $type = null)
	{
		$this->model = $model;
		$this->name = $name;
		$this->type = $type;
	}
	
	/**
	 * @param DOMElement $xmlElement
	 */
	public function initialize($xmlElement)
	{
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;
			$tv = trim($value);
			if ($tv == '' || $tv != $value)
			{
				throw new \RuntimeException('Invalid empty or spaced attribute value for ' . $name);
			}	
			switch ($name)
			{
				case "name":
					if (in_array(strtolower($value), self::$RESERVED_PROPERTY_NAMES))
					{
						throw new \RuntimeException('Invalid name attribute value: ' . $value);
					}
					$this->name = $value;
					break;
				case "type":
					if (!in_array($value, static::$TYPES))
					{
						throw new \RuntimeException('Invalid type attribute value: ' . $value);
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
					if ($value == 'description' || $value == 'property' || $value == 'none')
					{
						$this->indexed = $value;
					}
					else
					{
						throw new \RuntimeException('Invalid indexed attribute value: '. $value);
					}
					break;
				case "cascade-delete":
					if ($value === 'true' || $value === 'false')
					{
						$this->cascadeDelete = ($value === 'true');
					}
					else
					{
						throw new \RuntimeException('Invalid cascade-delete attribute value: '. $value);
					}
					break;
				case "default-value":
					$this->defaultValue = $value;
					break;
				case "required":
					if ($value === 'true')
					{
						$this->required = ($value === 'true');
					}
					else
					{
						throw new \RuntimeException('Invalid required attribute value: '. $value);
					}
					break;
				case "min-occurs":
					$this->minOccurs = intval($value);
					if ($this->minOccurs <= 1)
					{
						throw new \RuntimeException('Invalid min-occurs attribute value: '. $value);
					}
					break;
				case "max-occurs":
					$this->maxOccurs = intval($value);
					if ($this->maxOccurs != -1 && $this->maxOccurs < 1)
					{
						throw new \RuntimeException('Invalid max-occurs attribute value: '. $value);
					}
					break;
				case "localized":
					if ($value === 'true')
					{
						$this->localized = true;
					}
					else
					{
						throw new \RuntimeException('Invalid localized attribute value: '. $value);
					}
					break;
				default:
					throw new \RuntimeException('Invalid property attribute ' . $name . ' = ' . $value);
					break;
			}
		}
		
		if ($this->getName() === null)
		{
			throw new \RuntimeException('Property Name can not be null');
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
					$this->constraintArray[$name] = $params;
				}
				else
				{
					throw new \RuntimeException('Invalid constraint name');
				}
			}
			elseif ($node->nodeType == XML_ELEMENT_NODE)
			{
				throw new \RuntimeException('Invalid property children node ' . $this->getName() . ' -> ' . $node->nodeName);
			}
		}
	}
	
	/**
	 * 
	 * @return \Change\Documents\Generators\Model
	 */
	public function getModel()
	{
		return $this->model;
	}
	
	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public function getParent()
	{
		return $this->parent;
	}
	
	/**
	 * @param \Change\Documents\Generators\Property $parent
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
	}
	
	/**
	 * @return \Change\Documents\Generators\Property[]
	 */
	public function getAncestors()
	{
		if ($this->parent)
		{
			$ancestors = $this->parent->getAncestors();
			$ancestors[] = $this->parent;
			return $ancestors;
		}
		return array();
	}
	
	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public function getRoot()
	{
		return ($this->parent) ? $this->parent->getRoot() : $this;
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
	 * @return boolean
	 */
	public function getLocalized()
	{
		return $this->localized;
	}

	/**
	 * @return array
	 */
	public function getConstraintArray()
	{
		return $this->constraintArray;
	}
	
	/**
	 * @return string
	 */
	public function getComputedType()
	{
		return $this->getRoot()->getType();
	}
	
	/**
	 * @return integer
	 */
	public function getComputedMinOccurs()
	{
		$p = $this;
		while ($p)
		{
			if ($p->getMinOccurs() !== null) {return $p->getMinOccurs();}
			$p = $p->getParent();
		}
		return 0;
	}
	
	/**
	 * @return integer
	 */
	public function getComputedMaxOccurs()
	{
		$p = $this;
		while ($p)
		{
			if ($p->getMaxOccurs() !== null) {return $p->getMaxOccurs();}
			$p = $p->getParent();
		}
		return -1;
	}
	
	/**
	 * @param boolean $localized
	 */
	public function makeLocalized($localized)
	{
		$this->localized = $localized;
	}
		
	/**
	 * @param string $string
	 */
	public function setDefaultValue($string)
	{
		$this->defaultValue = $string;
	}
	
	/**
	 * @throws \Exception
	 */
	public function validate()
	{					
		switch ($this->name)
		{
			case 'label':
				if ($this->type !== null)
				{
					$this->type = 'String';
					$this->required = true;
				}
				break;
			case 'voLCID':
			case 'LCID':
				$this->type = 'String';
				$this->constraintArray['maxSize'] = array('max' => 10);
				$this->required = true;
				break;
			case 'creationDate':
			case 'modificationDate':
				$this->type = 'DateTime';
				$this->required = true;
				break;
			case 'authorName':
				$this->type = 'String';
				$this->defaultValue = 'Anonymous';
				$this->constraintArray['maxSize'] = array('max' => 100);
				break;
			case 'authorId':
				$this->type = 'DocumentId';
				$this->documentType = 'Change_Users_User';
				break;
			case 'documentVersion':
				$this->type = 'Integer';
				$this->defaultValue = '0';
				$this->required = true;
				break;
			case 'publicationStatus':
				$this->type = 'String';
				$this->defaultValue = 'DRAFT';
				$this->required = true;
				break;
			case 'startPublication':
				$this->type = 'DateTime';
				break;
			case 'endPublication':
				$this->type = 'DateTime';
				break;
			case 'versionOfId':
				$this->type = 'DocumentId';
				$this->documentType = $this->model->getName();
				break;
		}
		$this->setDefaultConstraints();
	}
	
	/**
	 * @throws \Exception
	 */
	public function validateInheritance()
	{
		$pm = $this->getModel()->getParent();
		while ($pm)
		{
			$p = $pm->getPropertyByName($this->name);
			if ($p)
			{
				$this->setParent($p);
				break;
			}
			$pm = $pm->getParent();
		}
		$parentProp = $this->getParent();
		
		if ($parentProp === null)
		{
			if ($this->type === null)
			{
				$this->type = 'String';
				$this->setDefaultConstraints();
			}
		}
		else
		{
			if ($this->type !== null || $this->localized !== null)
			{
				throw new \RuntimeException('Invalid type redefinition attribute on ' . $this);
			}
		}
		
		$ancestors = $this->getAncestors();
		if ($this->model->checkLocalized())
		{
			switch ($this->name)
			{
				case 'voLCID':
				case 'correctionOfId':
				case 'versionOfId':			
					$this->makeLocalized(null);
					break;					
				case 'LCID':
				case 'creationDate':
				case 'modificationDate':

				case 'label':
				case 'authorName':
				case 'authorId':
				case 'documentVersion':
					
				case 'publicationStatus':
				case 'startPublication':
				case 'endPublication':
					$this->makeLocalized(true);
			}
		}
		elseif ($this->localized !== null)
		{
			throw new \RuntimeException('Invalid localized attribute on ' . $this);
		}
		
		$type = $this->getComputedType();
		if ($type !== 'DocumentArray')
		{
			if ($this->minOccurs !== null)
			{
				throw new \RuntimeException('Invalid min-occurs attribute on ' . $this);
			}
			if ($this->maxOccurs !== null)
			{
				throw new \RuntimeException('Invalid max-occurs attribute on ' . $this);
			}
		}
		else
		{
			$mi = $this->getComputedMinOccurs();
			$ma = $this->getComputedMaxOccurs();
			if ($ma != -1 && $ma < $mi)
			{
				throw new \RuntimeException('Invalid min-occurs max-occurs attribute value on ' . $this);
			}
		}
	}
	
	/**
	 * Set default constraints in the property.
	 */
	public function setDefaultConstraints()
	{
		if ($this->type === 'String')
		{
			if ($this->constraintArray === null || !isset($this->constraintArray['maxSize']))
			{
				$this->constraintArray['maxSize'] = array('max' => 255);
			}
		}
	}
		
	/**
	 * @return boolean
	 */
	public function hasRelation()
	{
		$type = $this->getRoot()->getType();
		return ($type === 'Document' || $type === 'DocumentArray' || $type === 'DocumentId');
	}
	
	/**
	 * @return boolean|number|string
	 */
	public function getDefaultPhpValue()
	{
		$val = $this->getDefaultValue();
		if ($val !== null)
		{
			$type = $this->getRoot()->getType();
			if ($type === 'Boolean')
			{
				return $val === 'true';
			}
			if ($type === 'Integer' || $type === 'DocumentId')
			{
				return intval($val);
			}
			if ($type === 'Float' || $type === 'Decimal')
			{
				return floatval($val);
			}
		}
		return $val;
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->model . '::' . $this->getName();
	}
}
