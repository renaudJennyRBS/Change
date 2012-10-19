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
	protected  $m_properties;
	
	/**
	 * @var \Change\Documents\Property[]
	 */
	protected  $m_invertProperties;
	
	/**
	 * @var \Change\Documents\Property[]
	 */
	protected  $m_serialisedproperties;
		
	/**
	 * @var string[]
	 */
	protected  $m_propertiesNames;
		
	/**
	 * @var string[]
	 */
	protected  $m_childrenNames;
	
	
	/**
	 * @var string[]
	 */
	protected  $m_ancestorsNames = array();
	
	/**
	 * @var string
	 */
	protected  $m_parentName;
	

	const BASE_MODEL = 'modules_generic/document';
	
	
	public function __construct()
	{
	}
	
	/**
	 * @return string For example: Change
	 */
	abstract public function getVendorName();
	
	/**
	 * @return string For example: generic
	 */
	abstract public function getModuleName();
	
	/**
	 * @return string For example: folder
	*/
	abstract public function getDocumentName();
	
	/**
	 * @return string
	 */
	public function getIcon()
	{
		return 'document';
	}

	/**
	 * @return string
	 */
	public function getLabelKey()
	{
		return 'm.' . $this->getModuleName() . '.document.'. $this->getDocumentName().'.document-name';
	}
	
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return \Change\I18n\I18nManager::getInstance()->trans($this->getLabelKey(), array('ucf'));
	}

	/**
	 * @return string For example: modules_generic/folder
	 */
	public function getName()
	{
		return 'modules_' . $this->getModuleName() . '/' . $this->getDocumentName();
	}

	/**
	 * @return boolean
	 */
	public function isLocalized()
	{
		return false;
	}
	
	/**
	 * @return boolean
	 */
	public function hasChildren()
	{
		return $this->m_childrenNames !== null;
	}
	
	/**
	 * @return string[]|NULL
	 */
	public function getChildrenNames()
	{
		return $this->m_childrenNames;
	}
	
	/**
	 * @return boolean
	 */
	public function hasParent()
	{
		return $this->m_parentName !== null;
	}
	
	/**
	 * @return string
	 */
	public function getParentName()
	{
		return $this->m_parentName;
	}
	
	/**
	 * @return string[]
	 */
	public function getAncestorModelNames()
	{
		return $this->m_ancestorsNames;
	}
	
	
	/**
	 * @return string[]
	 */
	public function getRootModelName()
	{
		$amn = $this->getAncestorModelNames();
		return (count($amn)) ? $amn[0] : $this->getName();
	}	

	/**
	 * @return boolean
	 */
	public function isIndexable()
	{
		return false;
	}
	
	/**
	 * @return boolean
	 */
	public function isBackofficeIndexable()
	{
		return true;
	}
	
	/**
	 * @param string $modelName
	 * @return boolean
	 */
	public function isModelCompatible($modelName)
	{
		switch ($modelName)
		{
			case self::BASE_MODEL:
			case $this->getName():
				return true;			
			default: 
				return in_array($modelName, $this->getAncestorModelNames());
		}
	}
	
	/**
	 * @return string
	 */
	public function getDefaultStatus()
	{
		return 'DRAFT';
	}


	protected function loadProperties()
	{
		$this->m_properties = array();
	}
	
	/**
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getPropertiesInfos()
	{
		if ($this->m_properties === null){$this->loadProperties();}
		return $this->m_properties;
	}
	
	/**
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getLocalizedPropertiesInfos()
	{
		if ($this->m_properties === null){$this->loadProperties();}
		$result = array();
		foreach ($this->m_properties as $name => $propertInfo)
		{
			/* @var $propertInfo PropertyInfo */
			if ($propertInfo->getLocalized()) {$result[$name] = $propertInfo;}
		}
		return $result;
	}
		
	/**
	 * @return array<string, \Change\Documents\Property>
	 */	
	public function getVisiblePropertiesInfos()
	{
		$sysProps = \Change\Application::getInstance()->getDocumentServices()->getModelManager()->getSystemPropertyNames();
		return array_diff_key($this->getEditablePropertiesInfos(), array_flip($sysProps));
	}

	/**
	 * @param string $propertyName
	 * @return \Change\Documents\Property
	 */
	public function getProperty($propertyName)
	{
		if ($this->m_properties === null){$this->loadProperties();}
		if (isset($this->m_properties[$propertyName]))
		{
			return $this->m_properties[$propertyName];
		}
		return null;
	}
	
	protected function loadSerialisedProperties()
	{
		$this->m_serialisedproperties = array();
	}
	
	/**
	 * @return array<string, \Change\Documents\Property>
	 */
	public function getSerializedPropertiesInfos()
	{
		if ($this->m_serialisedproperties === null) {$this->loadSerialisedProperties();}
		return $this->m_serialisedproperties;
	}
	
	/**
	 * @param string $propertyName
	 * @return \Change\Documents\Property
	 */	
	public function getSerializedProperty($propertyName)
	{
		if ($this->m_serialisedproperties === null) {$this->loadSerialisedProperties();}
		if (isset($this->m_serialisedproperties[$propertyName]))
		{
			return $this->m_serialisedproperties[$propertyName];
		}
		return null;
	}	
	
	/**
	 * @return array<string, \Change\Documents\Property>
	 */	
	public function getEditablePropertiesInfos()
	{
		if ($this->m_properties === null){$this->loadProperties();}
		if ($this->m_serialisedproperties === null) {$this->loadSerialisedProperties();}
		return array_merge($this->m_properties, $this->m_serialisedproperties);
	}	
		
	/**
	 * @param string $propertyName
	 * @return \Change\Documents\Property
	 */	
	public function getEditableProperty($propertyName)
	{
		if ($this->m_properties === null){$this->loadProperties();}
		if (isset($this->m_properties[$propertyName]))
		{
			return $this->m_properties[$propertyName];
		} 
		
	
		if ($this->m_serialisedproperties === null) {$this->loadSerialisedProperties();}
		if (isset($this->m_serialisedproperties[$propertyName]))
		{
			return $this->m_serialisedproperties[$propertyName];
		}
		
		return null;
	}	

	/**
	 * @return \Change\Documents\Property[]
	 */
	public function getIndexedPropertiesInfos()
	{
		$result = array();
		foreach ($this->getEditablePropertiesInfos() as $propertyName => $property) 
		{
			/* @var $property PropertyInfo */
			if ($property->isIndexed())
			{
				$result[$propertyName] = $property;
			}
		}
		return $result;
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isTreeNodeProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : $property->isTreeNode();
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isDocumentProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : $property->isDocument();
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isArrayProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : $property->isArray();
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isUniqueProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : $property->isUnique();
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : true;
	}

	/**
	 * @return string[]
	 */
	public function getPropertiesNames()
	{
		if ($this->m_propertiesNames === null)
		{
			$this->m_propertiesNames = array();
			foreach ($this->getPropertiesInfos() as $name => $infos)
			{
				if ($name != 'id' && $name != 'model')
				{
					$this->m_propertiesNames[] = $name;
				}
			}
		}
		return $this->m_propertiesNames;
	}

	/**
	 * @param string $type
	 * @return string[]
	 */
	public function findTreePropertiesNamesByType($type)
	{
		$componentNames = array();
		foreach ($this->getPropertiesInfos() as $name => $infos)
		{
			if ($infos->isTreeNode() && $infos->isDocument() && $infos->acceptType($type))
			{
				$componentNames[] = $name;
			}
		}

		foreach ($this->getInverseProperties() as $name => $infos)
		{
			/* @var $infos PropertyInfo */
			if ($infos->getTreeNode() && $infos->isDocument() && $infos->acceptType($type))
			{
				// The most specific is suposed to be the last one.
				// Cf generator_PersistentModel::generatePhpModel().
				$componentNames[$infos->getDbTable() . '.' . $infos->getDbMapping()] = $name;
			}
		}
		return array_values($componentNames);
	}
	
	/**
	 * @return boolean
	 */
	public function hasCascadeDelete()
	{
		foreach ($this->getPropertiesInfos() as $name => $info)
		{
			if ($info->isCascadeDelete())
			{
				return true;
			}
		}
		return false;
	}

	protected function loadInvertProperties()
	{
		$this->m_invertProperties = array();
	}

	/**
	 * @return \Change\Documents\Property[]
	 */
	public function getInverseProperties()
	{
		if ($this->m_invertProperties === null) {$this->loadInvertProperties();}
		return $this->m_invertProperties;
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasInverseProperty($name)
	{
		if ($this->m_invertProperties === null) {$this->loadInvertProperties();}
		return isset($this->m_invertProperties[$name]);
	}

	/**
	 * @param string $name
	 * @return \Change\Documents\Property|null
	 */
	public function getInverseProperty($name)
	{
		if ($this->m_invertProperties === null) {$this->loadInvertProperties();}
		if (isset($this->m_invertProperties[$name]))
		{
			return $this->m_invertProperties[$name];
		}
		return null;
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function hasProperty($propertyName)
	{
		return $this->isProperty($propertyName);
	}

	/**
	 * Return if the document has 2 special properties (correctionid, correctionofid)
	 * @return boolean
	 */
	public function useCorrection()
	{
		return false;
	}

	/**
	 * @return boolean
	 */
	public function hasWorkflow()
	{
		return false;
	}

	/**
	 * @return string
	 */
	public function getWorkflowStartTask()
	{
		return null;
	}
	
	/**
	 * @return array<String, String>
	 */
	public function getWorkflowParameters()
	{
		return array();
	}

	/**
	 * @return boolean
	 */
	public function usePublicationDates()
	{
		return true;
	}
		
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getName();
	}
}