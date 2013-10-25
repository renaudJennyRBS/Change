<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractModel
 * @api
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
	 * @var ModelManager
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
	 * @var string|boolean
	 */
	protected $treeName = false;

	/**
	 * @var string
	 */
	protected $replacedBy;

	/**
	 * @param ModelManager $modelManager
	 */
	public function __construct(ModelManager $modelManager)
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
	 * @return string For example: Rbs_Generic
	 */
	public function getModuleName()
	{
		return $this->getVendorName() . '_' . $this->getShortModuleName();
	}

	/**
	 * @api
	 * @return string For example: Rbs_Generic_Folder
	 */
	public function getName()
	{
		return $this->getVendorName() . '_' . $this->getShortModuleName() . '_' . $this->getShortName();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isStateless()
	{
		return false;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isAbstract()
	{
		return false;
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
	public function hasUrl()
	{
		return false;
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
	public function isActivable()
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
	public function useTree()
	{
		return $this->treeName !== false;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getTreeName()
	{
		return is_string($this->treeName) ? $this->treeName : null;
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
	public function getReplacedBy()
	{
		return $this->replacedBy;
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
			return $this->ancestorsNames[count($this->ancestorsNames) - 1];
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

		$p = new Property('id', 'Integer');
		$this->properties['id'] = $p->setRequired(true);

		$p =  new Property('model', 'String');
		$this->properties['model'] = $p->setRequired(true)->setDefaultValue($this->getName());

	}

	/**
	 * @api
	 * @return \Change\Documents\Property[] keys assumed as property names.
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @api
	 * @return \Change\Documents\Property[] keys assumed as property names.
	 */
	public function getLocalizedProperties()
	{
		return array_filter($this->properties, function (Property $property)
		{
			return $property->getLocalized();
		});
	}

	/**
	 * @api
	 * @return \Change\Documents\Property[] keys assumed as property names.
	 */
	public function getNonLocalizedProperties()
	{
		if ($this->isLocalized())
		{
			return array_filter($this->properties, function (Property $property)
			{
				return !$property->getLocalized();
			});
		}
		return $this->properties;
	}

	/**
	 * @api
	 * @return \Change\Documents\Property[] keys assumed as property names.
	 */
	public function getPropertiesWithCorrection()
	{
		return array_filter($this->properties, function (Property $property)
		{
			return $property->getHasCorrection();
		});
	}

	/**
	 * @api
	 * @return \Change\Documents\Property[] keys assumed as property names.
	 */
	public function getLocalizedPropertiesWithCorrection()
	{
		return array_filter($this->properties, function (Property $property)
		{
			return $property->getLocalized() && $property->getHasCorrection();
		});
	}

	/**
	 * @api
	 * @return \Change\Documents\Property[] keys assumed as property names.
	 */
	public function getNonLocalizedPropertiesWithCorrection()
	{
		if ($this->isLocalized())
		{
			return array_filter($this->properties, function (Property $property)
			{
				return !$property->getLocalized() && $property->getHasCorrection();
			});
		}
		return $this->getPropertiesWithCorrection();
	}

	/**
	 * @api
	 * @return \Change\Documents\Property[] keys assumed as property names.
	 */
	public function getIndexedProperties()
	{
		return array_filter($this->properties, function (Property $property)
		{
			return $property->isIndexed();
		});
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
	 * @param AbstractDocument|Interfaces\Publishable|Interfaces\Localizable|Interfaces\Editable|Interfaces\Activable $document
	 * @param string $propertyName
	 * @param mixed $defaultValue [optional]
	 * @return mixed|null
	 */
	public function getPropertyValue(AbstractDocument $document, $propertyName, $defaultValue = null)
	{
		if ($this->hasProperty($propertyName))
		{
			return $this->properties[$propertyName]->getValue($document);
		}
		return $defaultValue;
	}

	/**
	 * @api
	 * @param AbstractDocument|Interfaces\Publishable|Interfaces\Localizable|Interfaces\Editable|Interfaces\Activable $document
	 * @param string $propertyName
	 * @param mixed $value
	 * @return $this
	 */
	public function setPropertyValue(AbstractDocument $document, $propertyName, $value)
	{
		if ($this->hasProperty($propertyName))
		{
			$this->properties[$propertyName]->setValue($document, $value);
		}
		return $this;
	}

	/**
	 * @param string|AbstractModel $modelOrModelName
	 * @return bool
	 */
	public function isInstanceOf($modelOrModelName)
	{
		$modelName = ($modelOrModelName instanceof AbstractModel) ? $modelOrModelName->getName() : strval($modelOrModelName);
		if ($this->getName() === $modelName)
		{
			return true;
		}
		elseif (in_array($modelName, $this->getAncestorsNames()))
		{
			return true;
		}
		return false;
	}
	/**
	 * @api
	 * @return string[]
	 */
	public function getPropertyNames()
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
	 * @return \Change\Documents\InverseProperty[] keys assumed as property names.
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
	 * @param string $name
	 * @return string
	 */
	public function getPropertyLabelKey($name)
	{
		return strtolower(
			'm.' . $this->getVendorName() . '.' . $this->getShortModuleName() . '.documents.' . $this->getShortName() . '.'
				. strtolower($name));
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
		return strtolower('m.' . $this->getVendorName() . '.' . $this->getShortModuleName() . '.documents.' . $this->getShortName());
	}

	/**
	 * @api
	 * @return string
	 */
	abstract function getDocumentClassName();

	/**
	 * @api
	 * @return string
	 */
	abstract function getLocalizedDocumentClassName();

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getName();
	}
}