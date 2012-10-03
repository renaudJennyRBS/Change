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
	 * @var \Change\Documents\Generators\ChildrenProperty[]
	 */
	protected $childrenProperties = array();
	
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
						case 'children':
							$this->importChildrenProperties($xmlSectionNode);
							break;
						case 'workflow':
							$this->importWorkflow($xmlSectionNode);
							break;
						case 'statuses':
							$this->importPublicationStatus($xmlSectionNode);
							break;
							
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
				case "table-name":
					// just ignore it
					break;
				case "inject":
					$this->inject = ($value === 'true');
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
	 * @param \DOMElement $childrenPropertiesElement
	 */
	protected function importChildrenProperties($childrenPropertiesElement)
	{
		foreach ($childrenPropertiesElement->childNodes as $xmlProperty)
		{
			if ($xmlProperty->nodeName == "child")
			{
				$property = new ChildrenProperty();
				$property->initialize($xmlProperty);
				$this->childrenProperties[$property->getName()] = $property;
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
	 * @return string
	 */
	public function getExtend()
	{
		return $this->extend;
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
	public function getPHPNameSpace()
	{
		return 'ChangeCompilation\Modules\\' . ucfirst($this->getModuleName()) .'\Documents';
	}
	
	/**
	 * @return string
	 */
	public function getPHPModelClassName($addNameSpace = false)
	{
		$cn = ucfirst($this->getDocumentName()) . 'Model';
		return ($addNameSpace) ? '\\' . $this->getPHPNameSpace() . '\\' . $cn : $cn;
	}
	
	/**
	 * @return string
	 */
	public function getPHPDocumentClassName($addNameSpace = false)
	{
		$cn = ucfirst($this->getDocumentName());
		return ($addNameSpace) ? '\\' . $this->getPHPNameSpace() . '\\' . $cn : $cn;
	}
	
	/**
	 * @return string
	 */
	public function getPHPDocumentBaseClassName($addNameSpace = false)
	{
		$cn = ucfirst($this->getDocumentName()) . 'Base';
		return ($addNameSpace) ? '\\' . $this->getPHPNameSpace() . '\\' . $cn : $cn;
	}
	
	public function evaluatePreservedPropertiesNames()
	{
		return array_map(function($property) {return $property->getName();}, array_filter($this->properties, function($property) {return $property->getPreserveOldValue();}));	
	}
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler)
	{
		return '';
	}
	
}