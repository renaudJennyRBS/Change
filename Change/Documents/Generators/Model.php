<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\Model
 */
class Model
{	
	/**
	 * @var string
	 */
	protected $vendor;
	
	/**
	 * @var string
	 */
	protected $shortModuleName;
	
	/**
	 * @var string
	 */
	protected $shortName;

	/**
	 * @var boolean
	 */
	protected $stateless;

	/**
	 * @var string
	 */
	protected $treeName;
	
	/**
	 * @var \Change\Documents\Generators\Model
	 */
	protected $parent;
	
	/**
	 * @var \Change\Documents\Generators\Model
	 */
	protected $extendModel;
	
	/**
	 * @var Property
	 */
	protected $properties = array();
	
	/**
	 * @var InverseProperty
	 */
	protected $inverseProperties = array();	
	
	/**
	 * @var string
	 */
	protected $extend;
	
	/**
	 * @var boolean
	 */
	protected $inject;

	/**
	 * @var boolean
	 */
	protected $localized;
	
	/**
	 * @var string
	 */
	protected $icon;
	
	/**
	 * @var boolean
	 */
	protected $hasUrl;
	
	/**
	 * @var boolean
	 */
	protected $frontofficeIndexable;
	
	/**
	 * @var boolean
	 */
	protected $backofficeIndexable;
	
	/**
	 * @var boolean
	 */
	protected $publishable;
		
	/**
	 * @var boolean
	 */
	protected $useVersion;
	
	/**
	 * @var boolean
	 */
	protected $editable;
		
	/**
	 * @param string $vendor
	 * @param string $shortModuleName
	 * @param string $shortName
	 */
	public function __construct($vendor, $shortModuleName, $shortName)	
	{
		$this->vendor = $vendor;
		$this->shortModuleName = $shortModuleName;
		$this->shortName = $shortName;
	}
	
	/**
	 * @return string
	 */
	public function getVendor()
	{
		return $this->vendor;
	}
	
	/**
	 * @return string
	 */
	public function getShortModuleName()
	{
		return $this->shortModuleName;
	}
	
	/**
	 * @return string
	 */
	public function getShortName()
	{
		return $this->shortName;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->vendor . '_' . $this->shortModuleName . '_' . $this->shortName;
	}

	/**
	 * @param \DOMDocument $domDocument
	 * @throws \RuntimeException
	 */
	public function setXmlDocument($domDocument)
	{
		$this->importAttributes($domDocument->documentElement);
		if ($domDocument->documentElement)
		{
			foreach ($domDocument->documentElement->childNodes as $xmlSectionNode)
			{
				/* @var $xmlSectionNode \DOMElement */
				if ($xmlSectionNode->nodeType === XML_ELEMENT_NODE)
				{
					switch ($xmlSectionNode->localName)
					{
						case 'properties':
							$this->importProperties($xmlSectionNode);
							break;
						default:
							throw new \RuntimeException('Invalid properties node name ' . $this . ' ' . $xmlSectionNode->localName, 54008);
					}
				}
			}
		}
	}

	/**
	 * @param \DOMElement $xmlElement
	 * @throws \RuntimeException
	 */
	protected function importAttributes($xmlElement)
	{
		if ($xmlElement->localName !== 'document')
		{
			throw new \RuntimeException('Invalid document element name ' . $this, 54009);
			return;
		}
	
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;
			$tv = trim($value);
			if ($tv == '' || $tv != $value)
			{
				throw new \RuntimeException('Invalid empty attribute value for ' . $this . ' ' . $name, 54010);
			}	
			switch ($name)
			{
				case "stateless":
					if ($value === 'true')
					{
						$this->stateless = true;
					}
					else
					{
						throw new \RuntimeException('Invalid '.$name.' attribute value: ' . $value, 54022);
					}
					break;
				case "extend":
					$this->extend = $value;
					break;
				case "inject":
					$this->inject = ($value === 'true');
					break;
				case "icon":
					$this->icon = $value;
					break;
				case "has-url":
					$this->hasUrl = ($value === 'true');
					break;
				case "frontoffice-indexable":
					$this->frontofficeIndexable = ($value === 'true');
					break;
				case "backoffice-indexable":
					$this->backofficeIndexable = ($value === 'true');
					break;
				case "editable":
					$this->editable = ($value === 'true');
					break;
				case "publishable":
					$this->publishable = ($value === 'true');
					break;
				case "use-version":
					$this->useVersion = ($value === 'true');
					break;
				case "localized":
					$this->localized = ($value === 'true');
					break;
				case "tree-name":
					if ($value === 'false')
					{
						$this->treeName = false;
					}
					elseif ($value === 'true')
					{
						$this->treeName = true;
					}
					else
					{
						if (!preg_match('/^[A-Z][A-Za-z0-9]+_[A-Z][A-Za-z0-9]+$/', $value))
						{
							throw new \RuntimeException('Invalid '.$name.' attribute value: ' . $value, 54022);
						}
						$this->treeName = $value;
					}
					break;
				case "xsi:schemaLocation":
					// just ignore it
					break;
				default:
					throw new \RuntimeException('Invalid attribute name ' . $this . ' ' . $name . ' = ' . $value, 54011);
					break;
			}
		}
		
		if ($this->localized === false || $this->editable === false  || $this->publishable === false  || $this->inject === false)
		{
			throw new \RuntimeException('Invalid attribute value true expected', 54012);
		}

		if ($this->stateless)
		{
			if ($this->backofficeIndexable !== null)
			{
				$this->backofficeIndexable = false;
			}

			if  ($this->extend || $this->hasUrl || $this->frontofficeIndexable || $this->backofficeIndexable
				|| $this->localized || $this->editable || $this->publishable || $this->useVersion)
			{
				throw new \RuntimeException('Property stateless can not be applicable', 54024);
			}
		}
	}

	/**
	 * @param \DOMElement $propertiesElement
	 * @throws \RuntimeException
	 */
	protected function importProperties($propertiesElement)
	{
		foreach ($propertiesElement->childNodes as $xmlProperty)
		{
			if ($xmlProperty->nodeType === XML_ELEMENT_NODE)
			{
				if ($xmlProperty->nodeName == "property")
				{
					$property = new Property($this);
					$property->initialize($xmlProperty);
					$this->addProperty($property);
				}
				else
				{
					throw new \RuntimeException('Invalid property node name ' . $this. ' ' . $xmlProperty->nodeName, 54013);
				}
			}
		}
	}

	/**
	 * @param Property $property
	 * @throws \RuntimeException
	 */
	public function addProperty(Property $property)
	{
		if (isset($this->properties[$property->getName()]))
		{
			throw new \RuntimeException('Duplicate property name ' . $this. '::'. $property->getName(), 54014);
		}
		$this->properties[$property->getName()] = $property;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function validate()
	{
		if (strlen($this->getName()) > 50)
		{
			throw new \RuntimeException('Invalid document element name ' . $this .' too long', 54009);
		}
		if ($this->extend)
		{
			if ($this->localized !== null)
			{
				throw new \RuntimeException('Invalid localized attribute ' . $this, 54015);
			}
			if ($this->inject)
			{
				if ($this->publishable)
				{
					throw new \RuntimeException('inject ' .$this . ' as invalid publishable attribute', 54016);
				}
				if ($this->useVersion)
				{
					throw new \RuntimeException('inject ' .$this . ' as invalid use-version attribute', 54017);
				}
			}
		}
		else
		{
			if ($this->inject)
			{
				throw new \RuntimeException('Invalid inject attribute ' . $this, 54018);
			}
			
			$creationDate = new Property($this, 'creationDate', 'DateTime');
			$creationDate->setDefaultValue('now');
			$this->properties[$creationDate->getName()] = $creationDate;
			
			$modificationDate = new Property($this, 'modificationDate', 'DateTime');
			$modificationDate->setDefaultValue('now');
			$this->properties[$modificationDate->getName()] = $modificationDate;
			
			if ($this->localized)
			{
				$property = new Property($this, 'refLCID', 'String');
				$this->properties[$property->getName()] = $property;
				
				$property = new Property($this, 'LCID', 'String');
				$this->properties[$property->getName()] = $property;
			}
		}
		
		if ($this->editable)
		{
			if (!isset($this->properties['label']))
			{
				$property = new Property($this, 'label', 'String');
				$this->properties[$property->getName()] = $property;
			}
			
			$property = new Property($this, 'authorName', 'String');
			$this->properties[$property->getName()] = $property;

			$property = new Property($this, 'authorId', 'DocumentId');
			$this->properties[$property->getName()] = $property;

			$property = new Property($this, 'documentVersion', 'Integer');
			$this->properties[$property->getName()] = $property;

		}
		
		if ($this->publishable)
		{
			$property = new Property($this, 'publicationStatus', 'String');
			$this->properties[$property->getName()] = $property;
			
			$creationDate = new Property($this, 'startPublication', 'DateTime');
			$this->properties[$creationDate->getName()] = $creationDate;
			
			$property = new Property($this, 'endPublication', 'DateTime');
			$this->properties[$property->getName()] = $property;
		}
		
		if ($this->useVersion)
		{
			$property = new Property($this, 'versionOfId', 'DocumentId');
			$this->properties[$property->getName()] = $property;
		}
			
		foreach ($this->properties as $property)
		{
			/* @var $property Property */
			$property->validate();
		}
	}
	
	
	/**
	 * @throws \Exception
	 */
	public function validateInheritance()
	{	
		if ($this->getUseVersion() !== null)
		{
			if ($this->checkAncestorUseVersion())
			{
				throw new \RuntimeException('Duplicate use-version attribute on ' . $this, 54019);
			}
		}
		
		if ($this->getPublishable() !== null)
		{
			if ($this->checkAncestorPublishable())
			{
				throw new \RuntimeException('Duplicate publishable attribute on ' . $this, 54020);
			}
		}

		if ($this->getTreeName())
		{
			$addProperty = true;
			foreach ($this->getAncestors() as $am)
			{
				/* @var $am \Change\Documents\Generators\Model */
				if ($am->getPropertyByName('treeName'))
				{
					$addProperty = false;
					break;
				}
			}

			if ($addProperty)
			{
				$property = new Property($this, 'treeName', 'String');
				$property->validate();
				$this->properties[$property->getName()] = $property;
			}
		}
		
		foreach ($this->properties as $property)
		{
			/* @var $property Property */
			$property->validateInheritance();
		}
	}
	
	
	/**
	 * @return \Change\Documents\Generators\Model
	 */
	public function getExtendModel()
	{
		return $this->extendModel;
	}

	/**
	 * @param \Change\Documents\Generators\Model $extendModel
	 */
	public function setExtendModel($extendModel)
	{
		$this->extendModel = $extendModel;
	}

	/**
	 * @return \Change\Documents\Generators\Model
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @param \Change\Documents\Generators\Model $parent
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return Property
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param string $name
	 * @return Property|null
	 */
	public function getPropertyByName($name)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}

	/**
	 * @param string $name
	 * @return Property
	 */
	public function getAncestorsPropertyByName($name)
	{
		if ($this->parent)
		{
			$properties = $this->parent->getAncestorsPropertyByName($name);
			$prop = $this->parent->getPropertyByName($name);
			if ($prop)
			{
				$properties[] = $prop;
			}
			return $properties;
		}
		return array();
	}
	
	/**
	 * @return InverseProperty
	 */
	public function getInverseProperties()
	{
		return $this->inverseProperties;
	}

	/**
	 * @param string $name
	 * @return InverseProperty
	 */
	public function getInversePropertyByName($name)
	{
		return isset($this->inverseProperties[$name]) ? $this->inverseProperties[$name] : null;
	}
	
	/**
	 * @param InverseProperty $inverseProperty
	 * @return \Change\Documents\Generators\InverseProperty
	 */
	public function addInverseProperty($inverseProperty)
	{
		return $this->inverseProperties[$inverseProperty->getName()] = $inverseProperty;
	}

	/**
	 * @return boolean
	 */
	public function getStateless()
	{
		return $this->stateless;
	}


	/**
	 * @return string
	 */
	public function getTreeName()
	{
		return $this->treeName;
	}

	/**
	 * @return string
	 */
	public function getExtend()
	{
		return $this->extend;
	}
	
	/**
	 * @return boolean
	 */
	public function getInject()
	{
		return $this->inject;
	}	
	
	/**
	 * @return boolean
	 */
	public function getLocalized()
	{
		return $this->localized;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->icon;
	}

	/**
	 * @return boolean
	 */
	public function getHasUrl()
	{
		return $this->hasUrl;
	}

	/**
	 * @return boolean
	 */
	public function getFrontofficeIndexable()
	{
		return $this->frontofficeIndexable;
	}

	/**
	 * @return boolean
	 */
	public function getBackofficeIndexable()
	{
		return $this->backofficeIndexable;
	}
	
	
	/**
	 * @return boolean
	 */
	public function getPublishable()
	{
		return $this->publishable;
	}

	/**
	 * @return boolean
	 */
	public function getEditable()
	{
		return $this->editable;
	}

	/**
	 * @return boolean
	 */
	public function getUseVersion()
	{
		return $this->useVersion;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getName();
	}
		
	/**
	 * @param boolean $localized
	 */
	public function setLocalized($localized)
	{
		$this->localized = ($localized == true);
	}
	
	/**
	 * @return \Change\Documents\Generators\Model[]
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
	 * @return \Change\Documents\Generators\Model
	 */
	public function getRoot()
	{
		return ($this->parent) ? $this->parent->getRoot() : $this;
	}
	
	/**
	 * @return boolean
	 */
	public function checkLocalized()
	{
		return $this->getRoot()->getLocalized() == true;
	}

	/**
	 * @return boolean
	 */
	public function checkStateless()
	{
		return $this->getRoot()->getStateless() == true;
	}
	
	/**
	 * @return boolean
	 */
	public function checkHasCorrection()
	{
		foreach ($this->properties as $property)
		{
			if ($property->getHasCorrection())
			{
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * @return boolean
	 */
	public function checkAncestorPublishable()
	{
		foreach ($this->getAncestors() as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if ($model->getPublishable())
			{
				return true;
			}
		}
		return false;
	}


		
	/**
	 * @return boolean
	 */
	public function checkAncestorUseVersion()
	{
		foreach ($this->getAncestors() as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if ($model->getUseVersion())
			{
				return true;
			}
		}
	}
	
	/**
	 * @return string
	 */
	public function getNameSpace()
	{
		return implode('\\', array($this->getVendor(),  $this->getShortModuleName(), 'Documents'));
	}
	
	/**
	 * @return string
	 */
	public function getCompilationNameSpace()
	{
		return implode('\\', array('Compilation', $this->getVendor(),  $this->getShortModuleName(), 'Documents'));
	}
	
	/**
	 * @return string
	 */
	public function getShortModelClassName()
	{
		return $this->getShortName().'Model';
	}
	
	/**
	 * @return string
	 */
	public function getModelClassName()
	{
		if ($this->getInject())
		{
			return $this->getParent()->getModelClassName();
		}
		return '\\'. $this->getCompilationNameSpace() . '\\' . $this->getShortModelClassName();
	}
	
	/**
	 * @return string
	 */
	public function getShortBaseDocumentClassName()
	{
		return $this->getShortName();
	}

	/**
	 * @return string
	 */
	public function getBaseDocumentClassName()
	{
		if ($this->getInject())
		{
			return $this->getParent()->getBaseDocumentClassName();
		}
		return '\\'. $this->getCompilationNameSpace() . '\\' . $this->getShortBaseDocumentClassName();
	}
	
	/**
	 * @return string
	 */
	public function getShortDocumentClassName()
	{
		return $this->getShortName();
	}
	
	/**
	 * @return string
	 */
	public function getDocumentClassName()
	{
		if ($this->getInject())
		{
			return $this->getParent()->getDocumentClassName();
		}
		return '\\'. $this->getNameSpace() . '\\' . $this->getShortDocumentClassName();
	}
	
	
	/**
	 * @return string
	 */
	public function getShortDocumentLocalizedClassName()
	{
		return 'Localized' . $this->getShortName();
	}
	
	/**
	 * @return string
	 */
	public function getDocumentLocalizedClassName()
	{
		if ($this->getInject())
		{
			return $this->getParent()->getDocumentLocalizedClassName();
		}
		return '\\'. $this->getCompilationNameSpace(). '\\' . $this->getShortDocumentLocalizedClassName();
	}
}