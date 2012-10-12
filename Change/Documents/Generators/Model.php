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
	protected $moduleName;
	
	/**
	 * @var string
	 */
	protected $documentName;
	
	/**
	 * @var \Change\Documents\Generators\Property[]
	 */
	protected $properties = array();
	
	/**
	 * @var \Change\Documents\Generators\SerializedProperty[]
	 */	
	protected $serializedproperties = array();
	
	/**
	 * @var \Change\Documents\Generators\InverseProperty[]
	 */
	protected $inverseProperties = array();	
	
	/**
	 * @var \Change\Documents\Generators\Workflow
	 */
	protected $workflow;

	/**
	 * @var string
	 */	
	protected $extend;
	
	/**
	 * @var string
	 */
	protected $dbMapping;
	
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
	protected $useRewriteUrl;
	
	/**
	 * @var boolean
	 */	
	protected $indexable;
	
	/**
	 * @var boolean
	 */	
	protected $backofficeIndexable;
	
	/**
	 * @var string
	 */
	protected $modelVersion;
	
	/**
	 * @var boolean
	 */
	protected $useCorrection;
	
	/**
	 * @var boolean
	 */
	protected $usePublicationDates;
	
	/**
	 * @var boolean
	 */
	protected $inject;
	
	/**
	 * @var string
	 */
	protected $status;
	
	/**
	 * @var boolean
	 */
	protected $cmpLocalized;
	
	/**
	 * @var string[]
	 */
	protected $cmpPropNames = array();
		
	/**
	 * @param string $vendor
	 * @param string $moduleName
	 * @param string $documentName
	 */
	public function __construct($vendor, $moduleName, $documentName)	
	{
		$this->vendor = $vendor;
		$this->moduleName = $moduleName;
		$this->documentName = $documentName;
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
	public function getModuleName()
	{
		return $this->moduleName;
	}
	
	/**
	 * @return string
	 */
	public function getDocumentName()
	{
		return $this->documentName;
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
						case 'serializedproperties':
							$this->importSerializedProperties($xmlSectionNode);
							break;
						case 'workflow':
							$this->importWorkflow($xmlSectionNode);
							break;
						case 'statuses':
							$this->importPublicationStatus($xmlSectionNode);
							break;
						default:
							//TODO: don't use echo directly 
							//echo "Deprecated section: " . $xmlSectionNode->localName . " in " . $this->moduleName . '/' . $this->documentName, PHP_EOL;	
					}
				}
			}
		}
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 */
	public function validate($ancestors)
	{
		/* @var $pm \Change\Documents\Generators\Model */
		$pm = count($ancestors) ? end($ancestors) : null;
		if ($pm)
		{
			$parentLocalized = $pm->getCmpLocalized();
			$this->cmpPropNames = $pm->getCmpPropNames();
		}
		else
		{
			$parentLocalized = false;
			$this->cmpPropNames = array();
		}
		
		if ($this->getLocalized() === null)
		{
			$this->cmpLocalized = $parentLocalized;
		}
	
		if ($this->getUseCorrection())
		{
			if (count($this->getPropertyAncestors($ancestors, 'correctionid')) === 0)
			{
				$this->addCorrectionProperties();
			}
		}
		
		if (count($this->getSerializedproperties()))
		{
			if (count($this->getPropertyAncestors($ancestors, 's18s')) === 0)
			{
				$this->addS18sProperty();
			}
		}
		
		if ($parentLocalized !== $this->getCmpLocalized())
		{
			$localize = $this->getCmpLocalized() != null ?  $this->getCmpLocalized() : $this->getLocalized();
			$this->makeLocalized($ancestors, $localize);
		}
		
		foreach ($this->properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$name = $property->getName();
			$pancestors = $this->getPropertyAncestors($ancestors, $name);
			$property->validate($pancestors);
			if (!$property->getOverride())
			{
				if (in_array($name, $this->cmpPropNames))
				{
					throw new \Exception($this->getFullName() . ' has duplicate property: ' . $name);
				}
				$this->cmpPropNames[] = $name;
			}
		}
		
		foreach ($this->serializedproperties as $property)
		{
			/* @var $property \Change\Documents\Generators\SerializedProperty */
			$name = $property->getName();
			$pancestors = $this->getSerialisedPropertyAncestors($ancestors, $name);
			$property->validate($pancestors);
			if (!$property->getOverride())
			{
				if (in_array($name, $this->cmpPropNames))
				{
					throw new \Exception($this->getFullName() . ' has duplicate serialized property: ' . $name);
				}
				$this->cmpPropNames[] = $name;
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
			throw new \Exception('Invalid document element name');
			return;
		}
		
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;
			
			switch ($name)
			{
				case "extend":
					$this->extend = (trim($value) !== '') ? trim($value) : null;
					break;
				case "inject":
					$this->inject = ($value === 'true');
					break;
				case "table-name": //DEPRECATED
				case "db-mapping":
					$this->dbMapping = $value;
					break;
				case "icon":
					$this->icon = $value;
					break;
				case "has-url":
					$this->hasUrl = ($value === 'true');
					break;
				case "use-rewrite-url":
					$this->useRewriteUrl = ($value === 'true');
					break;
				case "indexable":
					$this->indexable = ($value === 'true');
					break;
				case "backoffice-indexable":
					$this->backofficeIndexable = ($value === 'true');
					break;
				case "model-version":
					$this->modelVersion = $value;
					break;
				case "use-correction":
					$this->useCorrection = ($value === 'true');
					break;
				case "use-publication-dates":
					$this->usePublicationDates = ($value === 'true');
					break;
				case "xsi:schemaLocation":
					// just ignore it
					break;
				default:
					throw new \Exception( $this->getFullName() . ' has invalid attribute ' . $name . ' = ' . $value);
					break;
			}
		}
	}
	
	/**
	 * @param \DOMElement $propertiesElement
	 */
	protected function importProperties($propertiesElement)
	{
		foreach ($propertiesElement->childNodes as $xmlProperty)
		{
			if ($xmlProperty->nodeName == "property")
			{
				$property = new Property();
				$property->initialize($xmlProperty);
				if (isset($this->properties[$property->getName()]))
				{
					throw new \Exception($this->getFullName() . ' has duplicat property name: ' . $property->getName());
				}
				$this->properties[$property->getName()] = $property;
				if ($property->getLocalized())
				{
					$this->setLocalized(true);
				}
			}
		}
		
		if (isset($this->properties['publicationstatus']))
		{
			/* @var $property Property */
			$property = $this->properties['publicationstatus'];
			if ($property->getDefaultValue() !== null)
			{
				if (in_array($property->getDefaultValue(), array('DRAFT','CORRECTION','ACTIVE','PUBLISHED','DEACTIVATED','FILED','DEPRECATED','TRASH','WORKFLOW')))
				{
					$this->status = $property->getDefaultValue();
				}
				else
				{
					throw new \Exception($this->getFullName() . ' has invalid publication status: ' . $property->getDefaultValue());
				}
			}
		}
	}
		
	/**
	 * @param \DOMElement $serializedPropertiesElement
	 */
	protected function importSerializedProperties($serializedPropertiesElement)
	{
		foreach ($serializedPropertiesElement->childNodes as $xmlProperty)
		{
			if ($xmlProperty->nodeName == "property")
			{
				$property = new SerializedProperty();
				$property->initialize($xmlProperty);
				if (isset($this->serializedproperties[$property->getName()]))
				{
					throw new \Exception($this->getFullName() . ' has duplicat serialized property name: ' . $property->getName());
				}
				$this->serializedproperties[$property->getName()] = $property;
			}
		}
	}
		
	/**
	 * @param \DOMElement $workflowElement
	 */
	protected function importWorkflow($workflowElement)
	{
		$this->workflow = new Workflow();
		$this->workflow->initialize($workflowElement);
		if ($this->workflow->getStartTask())
		{
			$this->useCorrection = true;
		}
	}
	
	public function getWorkflowStartTask()
	{
		if ($this->workflow)
		{
			$startTask = $this->workflow->getStartTask();
			return empty($startTask) ? null : $startTask;
		}
		return null;
	}
	
	public function getWorkflowParameters()
	{
		return ($this->workflow) ? $this->workflow->getParameters() : null;
	}
	
	/**
	 * @param \DOMElement $statusElement
	 */
	protected function importPublicationStatus($statusElement)
	{
		if ($statusElement->hasAttribute('default'))
		{
			$this->status = $statusElement->getAttribute('default');
		}
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
	 * @return \Change\Documents\Generators\SerializedProperty[]
	 */
	public function getSerializedproperties()
	{
		return $this->serializedproperties;
	}
	
	/**
	 * @return \Change\Documents\Generators\SerializedProperty
	 */
	public function getSerializedPropertyByName($name)
	{
		return isset($this->serializedproperties[$name]) ? $this->serializedproperties[$name] : null;
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
	 * @return string
	 */
	public function getDbMapping()
	{
		return $this->dbMapping;
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
	public function getUseRewriteUrl()
	{
		return $this->useRewriteUrl;
	}

	/**
	 * @return boolean
	 */
	public function getIndexable()
	{
		return $this->indexable;
	}

	/**
	 * @return boolean
	 */
	public function getBackofficeIndexable()
	{
		return $this->backofficeIndexable;
	}

	/**
	 * @return string
	 */
	public function getModelVersion()
	{
		return $this->modelVersion;
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
	public function getUsePublicationDates()
	{
		return $this->usePublicationDates;
	}

	/**
	 * @return boolean
	 */
	public function getInject()
	{
		return $this->inject;
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $defaultModel
	 */
	public function applyDefault(Model $defaultModel)
	{
		if ($this->getExtend() !== null)
		{
			throw new \Exception('Unable to apply default values on: ' . $this->getExtend());
		}
		if ($this->icon === null) {$this->icon = $defaultModel->getIcon();}
		if ($this->hasUrl === null) {$this->hasUrl = $defaultModel->getHasUrl();}
		if ($this->useRewriteUrl === null) {$this->useRewriteUrl = $defaultModel->getUseRewriteUrl();}
		if ($this->indexable === null) {$this->indexable = $defaultModel->getIndexable();}
		if ($this->backofficeIndexable === null) {$this->backofficeIndexable = $defaultModel->getBackofficeIndexable();}
		if ($this->modelVersion === null) {$this->modelVersion = $defaultModel->getModelVersion();}
		if ($this->useCorrection === null) {$this->useCorrection = $defaultModel->getUseCorrection();}
		if ($this->usePublicationDates === null) {$this->usePublicationDates = $defaultModel->getUsePublicationDates();}
		if ($this->inject === null) {$this->inject = $defaultModel->getInject();}
		if ($this->status === null) {$this->status = $defaultModel->getStatus();}
		if ($this->localized === null) {$this->localized = $defaultModel->getLocalized();}
		
		$newProperties = $this->properties;
		$this->properties = array();
		foreach ($defaultModel->getProperties() as $name => $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if (isset($newProperties[$name]))
			{
				$property->updateDefaultBy($newProperties[$name]);
				unset($newProperties[$name]);
			}
			$this->properties[$name] = $property; 
		}
		
		foreach ($newProperties as $name => $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$this->properties[$name] = $property; 
		}
	}
	
	/**
	 * @return string
	 */
	public function getFullName()
	{
		return strtolower($this->vendor . '_' . $this->moduleName . '_' . $this->documentName);
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getFullName();
	}
	
	/**
	 * @return string
	 */
	public function getNameSpace()
	{
		return implode('\\', array(ucfirst($this->getVendor()),  ucfirst($this->getModuleName()), 'Documents'));
	}
		
	/**
	 * @param boolean $localized
	 */
	public function setLocalized($localized)
	{
		$this->localized = ($localized == true);
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 * @param string $name
	 * @return \Change\Documents\Generators\Property[]
	 */
	public function getPropertyAncestors($ancestors, $name)
	{
		$result = array();
		foreach ($ancestors as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$properties = $model->getProperties();
			if (isset($properties[$name]))
			{
				$result[] =  $properties[$name];
			}
		}
		return $result;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 * @param string $name
	 * @return \Change\Documents\Generators\SerializedProperty[]
	 */
	public function getSerialisedPropertyAncestors($ancestors, $name)
	{
		$result = array();
		foreach ($ancestors as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$properties = $model->getSerializedproperties();
			if (isset($properties[$name]))
			{
				$result[] = $properties[$name];
			}
		}
		return $result;
	}
	
	public function addCorrectionProperties()
	{
		$p = Property::getNewCorrectionIdProperty();
		$this->properties[$p->getName()] = $p;
		
		$p = Property::getNewCorrectionOfIdProperty();
		$this->properties[$p->getName()] = $p;
	}
	
	public function addS18sProperty()
	{
		$p = Property::getNewS18sProperty();
		$this->properties[$p->getName()] = $p;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 * @param boolean $localized;
	 */
	protected function makeLocalized($ancestors, $localized)
	{
		foreach (array('label', 'publicationstatus', 'correctionid') as $name)
		{
			if (isset($this->properties[$name]))
			{
				$p = $this->properties[$name];
				$p->makeLocalized($localized);
			}
			else
			{
				$pAnsestors = $this->getPropertyAncestors($ancestors, $name);
				if (count($pAnsestors))
				{
					$p = Property::getNamedProperty($name);
					$p->makeLocalized($localized);
					$p->validate($pAnsestors);
					$this->properties[$name] = $p;
				}
			}
		}
	}
	
	/**
	 * @return boolean
	 */
	public function getCmpLocalized()
	{
		return $this->cmpLocalized;
	}
	
	/**
	 * @return string[]
	 */
	public function getCmpPropNames()
	{
		return $this->cmpPropNames;
	}
}