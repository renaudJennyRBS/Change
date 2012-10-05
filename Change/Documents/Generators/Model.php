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
	 * @param string $moduleName
	 * @param string $documentName
	 */
	public function __construct($moduleName, $documentName)	
	{
		$this->moduleName = $moduleName;
		$this->documentName = $documentName;
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
				case "localized":
					$this->localized = ($value === 'true');
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
					throw new \Exception('Invalid document attribute ' . $name . ' = ' . $value);
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
				$this->properties[$property->getName()] = $property;
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
	 * @return \Change\Documents\Generators\SerializedProperty[]
	 */
	public function getSerializedproperties()
	{
		return $this->serializedproperties;
	}
	
	/**
	 * @return \Change\Documents\Generators\InverseProperty[]
	 */
	public function getInverseProperties()
	{
		return $this->inverseProperties;
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
		return 'modules_' . $this->moduleName . '/' . $this->documentName;
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
		return implode('\\', array('ChangeCompilation', 'Modules',  ucfirst($this->getModuleName()), 'Documents'));
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 * return boolean
	 */
	public function getLocalizedByAncestors($ancestors)
	{
		foreach ($ancestors as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if ($model->getLocalized())
			{
				return true;
			}
		}
		return false;
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
	 * return boolean
	 */
	public function getUseCorrectionByAncestors($ancestors)
	{
		foreach ($ancestors as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if ($model->getUseCorrection())
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 * return boolean
	 */
	public function getHasSerializedPropertiesByAncestors($ancestors)
	{
		foreach ($ancestors as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if (count($model->getSerializedproperties()))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 * @param string $name
	 * @return \Change\Documents\Generators\Property|NULL
	 */
	public function getPropertyByAncestors($ancestors, $name)
	{
		foreach ($ancestors as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$properties = $model->getProperties();
			if (isset($properties[$name]))
			{
				return $properties[$name];
			}
		}
		return null;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 * @param string $name
	 * @return \Change\Documents\Generators\Property|NULL
	 */
	public function getDocumentTypeByAncestors($ancestors, $name)
	{
		foreach ($ancestors as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$properties = $model->getProperties();
			if (isset($properties[$name]))
			{
				/* @var $p \Change\Documents\Generators\Property */
				$p = $properties[$name];
				if ($p->getDocumentType())
				{
					return $p->getDocumentType();
				}
			}
		}
		return null;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $ancestors
	 * @param string $name
	 * @return \Change\Documents\Generators\Property|NULL
	 */
	public function getSerialisedPropertyByAncestors($ancestors, $name)
	{
		foreach ($ancestors as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$properties = $model->getSerializedproperties();
			if (isset($properties[$name]))
			{
				return $properties[$name];
			}
		}
		return null;
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
	 */
	public function makeLocalised($ancestors)
	{
		foreach (array('label', 'publicationstatus', 'correctionid') as $name)
		{
			if (isset($this->properties[$name]))
			{
				$p = $this->properties[$name];
				$p->makeLocalized();
			}
			elseif (($ap = $this->getPropertyByAncestors($ancestors, $name)) !== null)
			{
				$p = Property::getNamedProperty($name);
				$p->makeLocalized();
				$p->setOverride(true);
				$this->properties[$name] = $p;
			}
		}
	}
}