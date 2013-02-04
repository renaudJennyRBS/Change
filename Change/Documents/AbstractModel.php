<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractModel
 */
abstract class AbstractModel
{
	/**
	 * @var \Change\Documents\Property[]
	 */
	protected $properties;
	
	/**
	 * @var \Change\Documents\Property[]
	 */
	protected $inverseProperties;
	
	/**
	 * @var string[]
	 */
	protected $descendantsNames = array();
	
	/**
	 * @var string[]
	 */
	protected $ancestorsNames = array();
	
	/**
	 * @var \Change\Documents\ModelManager
	 */
	protected $modelManager;
	
	/**
	 * @var string
	 */
	protected $vendorName;
	
	/**
	 * @var string
	 */
	protected $shortModuleName;
	
	/**
	 * @var string
	 */
	protected $shortName;
	
	/**
	 * @var string
	 */
	protected $injectedBy;
	
	/**
	 * @param \Change\Documents\ModelManager $modelManager
	 */
	public function __construct(\Change\Documents\ModelManager $modelManager)
	{
		$this->modelManager = $modelManager;
		$this->loadProperties();
		$this->loadInverseProperties();
	}
	
	/**
	 * @api
	 * @return string For example: Change
	 */
	public function getVendorName()
	{
		return $this->vendorName;
	}
	
	/**
	 * @api
	 * @return string For example: Generic
	 */
	public function getShortModuleName()
	{
		return $this->shortModuleName;
	}
	
	/**
	 * @api
	 * @return string For example: Folder
	 */
	public function getShortName()
	{
		return $this->shortName;
	}
	
	/**
	 * @api
	 * @return string For example: Change_Generic
	 */
	public function getModuleName()
	{
		return $this->getVendorName() . '_' . $this->getShortModuleName();
	}
	
	/**
	 * @api
	 * @return string For example: Change_Generic_Folder
	 */
	public function getName()
	{
		return $this->getVendorName() . '_' . $this->getShortModuleName() . '_' . $this->getShortName();
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isLocalized()
	{
		return false;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isFrontofficeIndexable()
	{
		return false;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isBackofficeIndexable()
	{
		return true;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isIndexable()
	{
		return $this->isBackofficeIndexable() || $this->isFrontofficeIndexable();
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isEditable()
	{
		return false;
	}
		
	/**
	 * @api
	 * @return boolean
	 */
	public function isPublishable()
	{
		return false;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function useVersion()
	{
		return false;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function useCorrection()
	{
		return false;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function hasDescendants()
	{
		return count($this->descendantsNames) > 0;
	}
	
	/**
	 * @api
	 * @return string[]
	 */
	public function getDescendantsNames()
	{
		return $this->descendantsNames;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getInjectedBy()
	{
		return $this->injectedBy;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function hasParent()
	{
		return count($this->ancestorsNames) > 0;
	}
	
	/**
	 * @api
	 * @return string|null
	 */
	public function getParentName()
	{
		if ($this->hasParent())
		{
			return $this->ancestorsNames[count($this->ancestorsNames) -1];
		}
		return null;
	}
	
	/**
	 * @api
	 * @return string[]
	 */
	public function getAncestorsNames()
	{
		return $this->ancestorsNames;
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getRootName()
	{
		$amn = $this->getAncestorsNames();
		return (count($amn)) ? $amn[0] : $this->getName();
	}

	/**
	 * @return void
	 */
	protected function loadProperties()
	{
		$this->properties = array();
		$p = $this->properties['id'] = new \Change\Documents\Property('id', 'Integer');
		$p->setRequired(true);
		
		$p = $this->properties['model'] = new \Change\Documents\Property('model', 'String');
		$p->setRequired(true)->setDefaultValue($this->getName());
	}
	
	/**
	 * @api
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getProperties()
	{
		return $this->properties;
	}
	
	/**
	 * @api
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getLocalizedProperties()
	{
		return array_filter($this->properties, function(\Change\Documents\Property $property) {return $property->getLocalized();});
	}
	
	/**
	 * @api
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getNonLocalizedProperties()
	{
		if ($this->isLocalized())
		{
			return array_filter($this->properties, function(\Change\Documents\Property $property) {return !$property->getLocalized();});
		}
		return $this->properties;
	}
	
	/**
	 * @api
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getPropertiesWithCorrection()
	{
		return array_filter($this->properties, function(\Change\Documents\Property $property) {return $property->getHasCorrection();});
	}
	
	/**
	 * @api
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getLocalizedPropertiesWithCorrection()
	{
		return array_filter($this->properties, function(\Change\Documents\Property $property) {return $property->getLocalized() && $property->getHasCorrection();});
	}

	/**
	 * @api
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getNonLocalizedPropertiesWithCorrection()
	{
		if ($this->isLocalized())
		{
			return array_filter($this->properties, function(\Change\Documents\Property $property) {return !$property->getLocalized() && $property->getHasCorrection();});
		}
		return $this->getPropertiesWithCorrection();
	}
	
	/**
	 * @api
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getIndexedProperties()
	{
		return array_filter($this->properties, function(\Change\Documents\Property $property) {return $property->isIndexed();});
	}
	
	/**
	 * @api
	 * @param string $propertyName
	 * @return boolean
	 */
	public function hasProperty($propertyName)
	{
		return isset($this->properties[$propertyName]);
	}
	
	/**
	 * @api
	 * @param string $propertyName
	 * @return \Change\Documents\Property|null
	 */
	public function getProperty($propertyName)
	{
		if ($this->hasProperty($propertyName))
		{
			return $this->properties[$propertyName];
		}
		return null;
	}
	
	/**
	 * @api
	 * @return string[]
	 */
	public function getPropertiesNames()
	{
		return array_keys($this->properties);
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function hasCascadeDelete()
	{
		foreach ($this->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getCascadeDelete())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return void
	 */
	protected function loadInverseProperties()
	{
		$this->inverseProperties = array();
	}

	/**
	 * @api
	 * @return array<string, \Change\Documents\InverseProperty>
	 */
	public function getInverseProperties()
	{
		return $this->inverseProperties;
	}
	
	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function hasInverseProperty($name)
	{
		return isset($this->inverseProperties[$name]);
	}

	/**
	 * @api
	 * @param string $name
	 * @return \Change\Documents\InverseProperty|null
	 */
	public function getInverseProperty($name)
	{
		if ($this->hasInverseProperty($name))
		{
			return $this->inverseProperties[$name];
		}
		return null;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getIcon()
	{
		return 'document';
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getLabelKey()
	{
		return strtolower('m.' . $this->getVendorName() . '.' . $this->getShortModuleName() . '.document.'. $this->getShortName().'.document-name');
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getName();
	}
}