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
	 * @var \Change\Documents\Generators\Model
	 */
	protected $parent;
	
	/**
	 * @var \Change\Documents\Generators\Model
	 */
	protected $extendModel;
	
	/**
	 * @var \Change\Documents\Generators\Property[]
	 */
	protected $properties = array();
	
	/**
	 * @var \Change\Documents\Generators\InverseProperty[]
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
	protected $useCorrection;
	
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
							throw new \Exception('Invalid properties node name ' . $this . ' ' . $xmlSectionNode->localName);	
					}
				}
			}
		}
	}
	
	/**
	 * @param \DOMElement $xmlElement
	 */
	protected function importAttributes($xmlElement)
	{
		if ($xmlElement->localName !== 'document')
		{
			throw new \Exception('Invalid document element name ' . $this);
			return;
		}
	
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;
			$tv = trim($value);
			if ($tv == '' || $tv != $value)
			{
				throw new \Exception('Invalid empty attribute value for ' . $this . ' ' . $name);
			}	
			switch ($name)
			{
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
				case "use-correction":
					$this->useCorrection = ($value === 'true');
					break;
				case "use-version":
					$this->useVersion = ($value === 'true');
					break;
				case "localized":
					$this->localized = ($value === 'true');
					break;
				case "xsi:schemaLocation":
					// just ignore it
					break;
				default:
					throw new \Exception('Invalid attribute name ' . $this . ' ' . $name . ' = ' . $value);
					break;
			}
		}
		
		if ($this->localized === false || $this->editable === false  || $this->publishable === false  || $this->inject === false)
		{
			throw new \Exception('Invalid attribute value true expected');
		}
	}
	
	/**
	 * @param \DOMElement $propertiesElement
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
					if (isset($this->properties[$property->getName()]))
					{
						throw new \Exception('Duplicate property name ' . $this. '::'. $property->getName());
					}
					$this->properties[$property->getName()] = $property;
				}
				else
				{
					throw new \Exception('Invalid property node name ' . $this. ' ' . $xmlProperty->nodeName);
				}
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	public function validate()
	{
		if ($this->extend)
		{
			if ($this->localized !== null)
			{
				throw new \Exception('Invalid localized attribute ' . $this);
			}
			if ($this->inject)
			{
				if ($this->publishable)
				{
					throw new \Exception('inject ' .$this . ' as invalid publishable attribute');
				}
				if ($this->useVersion)
				{
					throw new \Exception('inject ' .$this . ' as invalid use-version attribute');
				}
				if ($this->useCorrection)
				{
					throw new \Exception('inject ' .$this . ' as invalid use-correction attribute');
				}
			}
		}
		else
		{
			if ($this->inject)
			{
				throw new \Exception('Invalid inject attribute ' . $this);
			}
			
			$creationDate = new Property($this, 'creationDate', 'DateTime');
			$this->properties[$creationDate->getName()] = $creationDate;
			
			$modificationDate = new Property($this, 'modificationDate', 'DateTime');
			$this->properties[$modificationDate->getName()] = $modificationDate;
			
			$deletedDate = new Property($this, 'deletedDate', 'DateTime');
			$this->properties[$deletedDate->getName()] = $deletedDate;
			
			if ($this->localized)
			{
				$property = new Property($this, 'voLCID', 'String');
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
						
			if ($this->useCorrection === null)
			{
				$this->useCorrection = true;
			}
		}
		
		if ($this->useCorrection)
		{
			$property = new Property($this, 'correctionOfId', 'DocumentId');
			$this->properties[$property->getName()] = $property;
		}
		
		if ($this->useVersion)
		{
			$property = new Property($this, 'versionOfId', 'DocumentId');
			$this->properties[$property->getName()] = $property;
		}
			
		foreach ($this->properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$property->validate();
		}
	}
	
	
	/**
	 * @throws \Exception
	 */
	public function validateInheritance()
	{
		if ($this->getUseCorrection() !== null)
		{
			if ($this->checkAncestorUseCorrection())
			{
				throw new \Exception($this . ' as duplicate use-correction attribute');
			}
		}
		
		if ($this->getUseVersion() !== null)
		{
			if ($this->checkAncestorUseVersion())
			{
				throw new \Exception('Duplicate use-version attribute on ' . $this);
			}
		}
		
		if ($this->getPublishable() !== null)
		{
			if ($this->checkAncestorPublishable())
			{
				throw new \Exception('Duplicate publishable attribute on ' . $this);
			}
		}
		
		if (!$this->getPublishable() && !$this->checkAncestorPublishable())
		{
			if ($this->getUseCorrection())
			{
				throw new \Exception('Invalid usage of use-correction attribute on ' . $this);
			}
		}
		
		foreach ($this->properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
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
	 * @return \Change\Documents\Generators\Property[]
	 */
	public function getProperties()
	{
		return $this->properties;
	}
	
	/**
	 * @return \Change\Documents\Generators\Property
	 */
	public function getPropertyByName($name)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}
	
	/**
	 * @return \Change\Documents\Generators\Property[]
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
	 * @return \Change\Documents\Generators\InverseProperty[]
	 */
	public function getInverseProperties()
	{
		return $this->inverseProperties;
	}

	/**
	 * @return \Change\Documents\Generators\InverseProperty
	 */
	public function getInversePropertyByName($name)
	{
		return isset($this->inverseProperties[$name]) ? $this->inverseProperties[$name] : null;
	}
	
	/**
	 * @param \Change\Documents\Generators\InverseProperty $inverseProperty
	 */
	public function addInverseProperty($inverseProperty)
	{
		return $this->inverseProperties[$inverseProperty->getName()] = $inverseProperty;
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
	public function getUseCorrection()
	{
		return $this->useCorrection;
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
		return $this->getRoot()->getLocalized();
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
	public function checkAncestorUseCorrection()
	{
		foreach ($this->getAncestors() as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if ($model->getUseCorrection())
			{
				return true;
			}
		}
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
	public function getShortAbstractDocumentClassName()
	{
		return 'Abstract' . $this->getShortName();
	}

	/**
	 * @return string
	 */
	public function getAbstractDocumentClassName()
	{
		if ($this->getInject())
		{
			return $this->getParent()->getAbstractDocumentClassName();
		}
		return '\\'. $this->getCompilationNameSpace() . '\\' . $this->getShortAbstractDocumentClassName();
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
	public function getShortDocumentI18nClassName()
	{
		return $this->getShortName().'I18n';
	}
	
	/**
	 * @return string
	 */
	public function getDocumentI18nClassName()
	{
		if ($this->getInject())
		{
			return $this->getParent()->getDocumentI18nClassName();
		}
		return '\\'. $this->getCompilationNameSpace(). '\\' . $this->getShortDocumentI18nClassName();
	}	
	
	
	/**
	 * @return string
	 */
	public function getShortAbstractServiceClassName()
	{
		return 'Abstract' . $this->getShortName() . 'Service';
	}
	
	/**
	 * @return string
	 */
	public function getAbstractServiceClassName()
	{
		if ($this->getInject())
		{
			return $this->getParent()->getAbstractServiceClassName();
		}
		return '\\'. $this->getCompilationNameSpace() . '\\' . $this->getShortAbstractServiceClassName();
	}	
	
	/**
	 * @return string
	 */
	public function getShortServiceClassName()
	{
		return $this->getShortName() . 'Service';
	}
	
	/**
	 * @return string
	 */
	public function getServiceClassName()
	{
		if ($this->getInject())
		{
			return $this->getParent()->getServiceClassName();
		}
		return '\\'. $this->getNameSpace() . '\\' . $this->getShortServiceClassName();
	}
}