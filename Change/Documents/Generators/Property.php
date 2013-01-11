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
			'creationdate', 'modificationdate', 'deleteddate',
			'authorname', 'authorid', 'documentversion',
			'publicationstatus', 'startpublication', 'endpublication',
			'correctionofid', 'versionofid');
	
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
	protected $dbSize;


	/**
	 * @var boolean
	 */
	protected $localized;
		
	/**
	 * @var array
	 */
	protected $constraintArray;
	
	
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
				throw new \Exception('Invalid empty or spaced attribute value for ' . $name);
			}	
			switch ($name)
			{
				case "name":
					if (in_array(strtolower($value), self::$RESERVED_PROPERTY_NAMES))
					{
						throw new \Exception('Invalid property Name => ' . $value);
					}
					$this->name = $value;
					break;
				case "type":
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
					if ($value == 'description' || $value == 'property' || $value == 'none')
					{
						$this->indexed = $value;
					}
					else
					{
						throw new \Exception('Invalid indexed attribute value ' . $name . ' = ' . $value);
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
				case "db-size":
					$this->dbSize = $value;
					break;
				case "localized":
					$this->localized = ($value === 'true');
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
		
		if ($this->localized === false || $this->required === false)
		{
			throw new \Exception('Invalid attribute value true expected');
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
					throw new \Exception('Invalid constraint name');
				}
			}
			elseif ($node->nodeType == XML_ELEMENT_NODE)
			{
				throw new \Exception('Invalid property children node ' . $this->getName() . ' -> ' . $node->nodeName);
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
	public function getDbSize()
	{
		return $this->dbSize;
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
	 * Set default constraints in the property.
	 */
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
		if ($this->minOccurs === 0 || $this->minOccurs === 1)
		{
			throw new \Exception('Invalid min-occurs attribute on ' . $this->model . ':' . $this->name);
		}
			
		switch ($this->name)
		{
			case 'label':
				if ($this->type !== null)
				{
					$this->type = 'String';
					$this->dbSize = 255;
					$this->required = true;
				}
				break;
			case 'voLCID':
			case 'LCID':
					$this->type = 'String';
					$this->dbSize = 10;
					$this->required = true;
					break;
			case 'creationDate':
			case 'modificationDate':
				$this->required = true;
			case 'deletedDate':
				$this->type = 'DateTime';
				break;
			case 'authorName':
				$this->type = 'String';
				$this->defaultValue = 'Anonymous';
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
			case 'correctionOfId':
				$this->type = 'DocumentId';
				$this->documentType = $this->model->getName();
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
		
		if ($this->getParent() === null && $this->type === null)
		{
			$this->type = 'String';
			$this->dbSize = 255;
			$this->setDefaultConstraints();
		}
		elseif ($this->getParent() !== null && $this->type !== null)
		{
			throw new \Exception('Invalid type redefinition attribute on ' . $this->model . ':' . $this->name);
		}
		
		$ancestors = $this->getAncestors();
		if ($this->model->checkLocalized())
		{
			if ($this->localized)
			{
				foreach ($ancestors as $property)
				{
					/* @var $property \Change\Documents\Generators\Property */
					if ($property->getLocalized())
					{
						throw new \Exception('Invalid localized attribute on ' . $this->model . ':' . $this->name);
					}
				}
			}
			
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
				case 'deletedDate':
					
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
			throw new \Exception('Invalid localized attribute on ' . $this->model . ':' . $this->name);
		}
		
		$type = $this->getComputedType();
		if ($type !== 'DocumentArray')
		{
			if ($this->minOccurs !== null)
			{
				throw new \Exception('Invalid min-occurs attribute on ' . $this->model . ':' . $this->name);
			}
			if ($this->maxOccurs !== null)
			{
				throw new \Exception('Invalid max-occurs attribute on ' . $this->model . ':' . $this->name);
			}
		}
		else
		{
			$mi = $this->getComputedMinOccurs();
			$ma = $this->getComputedMaxOccurs();
			
			if ($mi < 0)
			{
				throw new \Exception('Invalid min-occurs attribute value on ' . $this->model . ':' . $this->name);
			}
			
			if ($ma < -1 || $ma == 0)
			{
				throw new \Exception('Invalid max-occurs attribute value on ' . $this->model . ':' . $this->name);
			}
			elseif ($ma != -1 && $ma < $mi)
			{
				throw new \Exception('Invalid min-occurs max-occurs attribute value on ' . $this->model . ':' . $this->name);
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
}
