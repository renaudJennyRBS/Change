<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractDocument
 * @api
 */
abstract class AbstractDocument implements \Serializable
{		
	/**
	 * @var integer
	 */
	private $persistentState = DocumentManager::STATE_NEW;

	/**
	 * @var integer
	 */
	private $id = 0;
	
	/**
	 * @var string
	 */
	private $documentModelName;

	/**
	 * @var array
	 */
	private $modifiedProperties = array();

	/**
	 * @var array<String,String|String[]>
	 */
	private $metas;
	
	/**
	 * @var boolean
	 */
	private $modifiedMetas = false;
	
	/**
	 * @var array
	 */
	private $propertiesErrors;
	
	/**
	 * @var \Change\Documents\CorrectionFunctions
	 */
	protected $correctionFunctions;
	
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;
	
	/**
	 * @var \Change\Documents\AbstractModel
	 */
	protected $documentModel;
	
	/**
	 * @var \Change\Documents\AbstractService
	 */
	protected $documentService;

	/**
	 * @param \Change\Documents\DocumentManager $manager
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Documents\AbstractService $service
	 */
	public function __construct(\Change\Documents\DocumentManager $manager, \Change\Documents\AbstractModel $model, \Change\Documents\AbstractService $service)
	{
		$this->setDocumentContext($manager, $model, $service);
	}

	public function __destruct()
	{
		unset($this->documentManager);
		unset($this->documentModel);
		unset($this->documentService);
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $manager
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Documents\AbstractService $service
	 */
	public function setDocumentContext(\Change\Documents\DocumentManager $manager, \Change\Documents\AbstractModel $model, \Change\Documents\AbstractService $service)
	{
		$this->documentManager = $manager;
		$this->documentModel = $model;
		$this->documentModelName = $model->getName();
		$this->documentService = $service;
	}

	/**
	 * This class is not serializable
	 * @return null
	 */
	public function serialize()
	{
		return null;
	}

	/**
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
		return;
	}

	/**
	 * @api
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}
	
	/**
	 * @api
	 * @return \Change\Documents\AbstractModel
	 */
	public function getDocumentModel()
	{
		return $this->documentModel;
	}
	
	/**
	 * @api
	 * @return string
	 */
	public function getDocumentModelName()
	{
		return $this->documentModelName;
	}
	
	/**
	 * @api
	 * @return \Change\Documents\AbstractService
	 */
	public function getDocumentService()
	{
		return $this->documentService;
	}
	
	/**
	 * @param integer $id
	 * @param integer $persistentState
	 */
	public function initialize($id, $persistentState = null)
	{
		$oldId = $this->id;
		$this->id = intval($id);
		if ($persistentState!== null)
		{
			$this->setPersistentState($persistentState);
		}
		$this->documentManager->reference($this, $oldId);
	}

	/**
	 * @api
	 */
	public function reset()
	{
		$this->modifiedProperties = array();
		$this->metas = null;
		$this->modifiedMetas = false;
		$this->propertiesErrors = null;
		$this->correctionFunctions = null;

		if ($this->persistentState === DocumentManager::STATE_LOADED)
		{
			$this->persistentState = DocumentManager::STATE_INITIALIZED;
		}
		elseif($this->persistentState === DocumentManager::STATE_NEW)
		{
			$this->setDefaultValues($this->documentModel);
		}
	}

	/**
	 * @param \Change\Documents\AbstractModel $documentModel
	 */
	public function setDefaultValues(\Change\Documents\AbstractModel $documentModel)
	{
		$this->persistentState = DocumentManager::STATE_NEW;
		foreach ($documentModel->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Property */
			if (!$property->getLocalized() && $property->getDefaultValue() !== null)
			{
				$property->setValue($this, $property->getDefaultValue());
			}
		}
		$this->clearModifiedProperties();
	}
	
	/**
	 * Persistent state list: \Change\Documents\DocumentManager::STATE_*
	 * @api
	 * @return integer
	 */
	public function getPersistentState()
	{
		return $this->persistentState;
	}

	/**
	 * Persistent state list: \Change\Documents\DocumentManager::STATE_*
	 * @api
	 * @param integer $newValue
	 * @return integer oldState
	 */
	public function setPersistentState($newValue)
	{
		$oldState = $this->persistentState;
		switch ($newValue) 
		{
			case DocumentManager::STATE_LOADED:
				$this->clearModifiedProperties();
			case DocumentManager::STATE_NEW:
			case DocumentManager::STATE_INITIALIZED:
			case DocumentManager::STATE_LOADING:	
			case DocumentManager::STATE_DELETED:
			case DocumentManager::STATE_SAVING:
				$this->persistentState = $newValue;
				break;
		}
		return $oldState;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isDeleted()
	{
		return $this->persistentState === DocumentManager::STATE_DELETED;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isNew()
	{
		return $this->persistentState === DocumentManager::STATE_NEW;
	}
	
	/**
	 * @api
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}
	
	
	
	protected function checkLoaded()
	{
		if ($this->persistentState === DocumentManager::STATE_INITIALIZED)
		{
			$this->documentManager->loadDocument($this);
		}
	}

	/**
	 * @api
	 */
	public function save()
	{
		if ($this->isNew())
		{
			$this->create();
		}
		else
		{
			$this->update();
		}
	}
	
	/**
	 * @api
	 */
	public function create()
	{
		$this->getDocumentService()->create($this);
	}
	
	/**
	 * @api
	 */
	public function update()
	{
		$this->getDocumentService()->update($this);
	}
	
	/**
	 * @api
	 */
	public function delete()
	{
		$this->getDocumentService()->delete($this);
	}
	
	/**
	 * Override by compiled document class
	 */
	protected function validateProperties()
	{
		foreach ($this->documentModel->getProperties() as $propertyName => $property)
		{
			if ($this->isNew() || $this->isPropertyModified($propertyName))
			{
				$this->validatePropertyValue($property);
			}
		}
	}
	
	/**
	 * @param \Change\Documents\Property $property
	 * @return boolean
	 */
	protected function validatePropertyValue($property)
	{
		$value = $property->getValue($this);
		if ($property->getType() === \Change\Documents\Property::TYPE_DOCUMENTARRAY)
		{
			$nbValue = count($value);
			if ($nbValue === 0)
			{
				if (!$property->isRequired())
				{
					return true;
				}
				$this->addPropertyError($property->getName(), new \Change\I18n\PreparedKey('c.constraints.isempty', array('ucf')));
				return false;
			}
			elseif ($property->getMaxOccurs() > 1 && $nbValue > $property->getMaxOccurs())
			{
				$args = array('maxOccurs' => $property->getMaxOccurs());
				$this->addPropertyError($property->getName(), new \Change\I18n\PreparedKey('c.constraints.maxoccurs', array('ucf'), array($args)));
				return false;
			}
			elseif ($property->getMinOccurs() > 1 && $nbValue < $property->getMinOccurs())
			{
				$args = array('minOccurs' => $property->getMinOccurs());
				$this->addPropertyError($property->getName(), new \Change\I18n\PreparedKey('c.constraints.minoccurs', array('ucf'), array($args)));
				return false;
			}
			
		} 
		elseif ($value === null || $value === '')
		{
			if (!$property->isRequired()) 
			{	
				return true;
			}
			$this->addPropertyError($property->getName(), new \Change\I18n\PreparedKey('c.constraints.isempty', array('ucf')));
			return false;
		}
		elseif ($property->hasConstraints()) 
		{
			$constraintManager = $this->documentService->getConstraintsManager();
			$defaultParams =  array('documentId' => $this->getId(),
									'modelName' => $this->getDocumentModelName(),
									'propertyName' => $property->getName(),
									'applicationServices' => $this->documentService->getApplicationServices(),
									'documentServices' => $this->documentService->getDocumentServices());
			foreach ($property->getConstraintArray() as $name => $params) 
			{
				$params += $defaultParams;
				$c = $constraintManager->getByName($name, $params);
				if (!$c->isValid($value)) 
				{
					$this->addPropertyErrors($property->getName(), $c->getMessages());
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * @api
	 * validate document and return boolean result
	 * @return boolean
	 */
	public function isValid()
	{
		$this->propertiesErrors = null;
		$this->validateProperties();
		return !$this->hasPropertiesErrors();
	}
	
	/**
	 * @api
	 * @return array<propertyName => string[]>
	 */
	public function getPropertiesErrors()
	{
		if ($this->hasPropertiesErrors())
		{
			return $this->propertiesErrors;
		}
		return array();
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	protected function hasPropertiesErrors()
	{
		return is_array($this->propertiesErrors) && count($this->propertiesErrors);
	}
	
	/**
	 * @param string $propertyName
	 * @param string $error
	 */
	protected function addPropertyError($propertyName, $error)
	{
		if ($error !== null)
		{
			$this->propertiesErrors[$propertyName][] = $error;
		}
	}
	
	/**
	 * @param string $propertyName
	 * @param string[] $errors
	 */
	protected function addPropertyErrors($propertyName, $errors)
	{		
		if (is_array($errors) && count($errors))
		{
			foreach ($errors as $error)
			{
				/* @var $error string */
				$this->addPropertyError($propertyName, $error);
			}
		}
	}	
	
	/**
	 * @param string $propertyName
	 */
	protected function clearPropertyErrors($propertyName = null)
	{
		if ($propertyName === null)
		{
			$this->propertiesErrors = null;
		}
		elseif (is_array($this->propertiesErrors) && isset($this->propertiesErrors[$propertyName]))
		{
			unset($this->propertiesErrors[$propertyName]);
		}
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasNonLocalizedModifiedProperties()
	{
		return count($this->modifiedProperties) > 0;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasModifiedProperties()
	{
		return $this->hasNonLocalizedModifiedProperties();
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getModifiedPropertyNames()
	{
		return array_keys($this->modifiedProperties);
	}
	
	/**
	 * @api
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isPropertyModified($propertyName)
	{
		return array_key_exists($propertyName, $this->modifiedProperties);
	}

	/**
	 * @param string $propertyName
	 * @return mixed
	 */
	protected function getOldPropertyValue($propertyName)
	{
		if (array_key_exists($propertyName, $this->modifiedProperties))
		{
			return $this->modifiedProperties[$propertyName];
		}
		return null;
	}

	/**
	 * @api
	 */
	protected function clearModifiedProperties()
	{
		$this->modifiedProperties = array();
	}
		
	/**
	 * @param string $propertyName
	 * @param mixed $value
	 */
	protected function setOldPropertyValue($propertyName, $value)
	{
		if (!array_key_exists($propertyName, $this->modifiedProperties))
		{
			$this->modifiedProperties[$propertyName] = $value;
		}
	}
	
	/**
	 * @api
	 * @param string $propertyName
	 */
	public function removeOldPropertyValue($propertyName)
	{
		if (array_key_exists($propertyName, $this->modifiedProperties))
		{
			unset($this->modifiedProperties[$propertyName]);
		}
	}

	/**
	 * Called every time a property has changed.
	 * @param string $propertyName Name of the property that has changed.
	 */
	protected function propertyChanged($propertyName)
	{
		$this->documentService->propertyChanged($this, $propertyName);
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractDocument $b
	 * @return boolean
	 */
	public function equals($b)
	{
		return $this === $b || (($b instanceof AbstractDocument) && $b->getId() === $this->getId());
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getDocumentModelName().' '.$this->getId();
	}
		
	// Metadata management

	/**
	 * @api
	 */
	public function saveMetas()
	{
		if ($this->modifiedMetas)
		{
			$this->documentService->saveMetas($this, $this->metas);
			$this->modifiedMetas = false;
		}
	}
	
	/**
	 * @return void
	 */
	protected function checkMetasLoaded()
	{
		if ($this->metas === null)
		{
			$this->metas = $this->documentManager->loadMetas($this);
			$this->modifiedMetas = false;
		}
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function hasModifiedMetas()
	{
		return $this->modifiedMetas;
	}
		
	/**
	 * @api
	 * @return array
	 */
	public function getMetas()
	{
		$this->checkMetasLoaded();
		return $this->metas;
	}
	
	/**
	 * @api
	 * @param array $metas
	 */
	public function setMetas($metas)
	{
		$this->checkMetasLoaded();
		if (count($this->metas))
		{
			$this->metas = array();
			$this->modifiedMetas = true;
		}
		if (is_array($metas))
		{
			foreach ($metas as $name => $value)
			{
				$this->metas[$name] = $value;
			}
			$this->modifiedMetas = true;
		}
	}
	
	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function hasMeta($name)
	{
		$this->checkMetasLoaded();
		return isset($this->metas[$name]);
	}

	/**
	 * @api
	 * @param string $name
	 * @return mixed
	 */
	public function getMeta($name)
	{
		$this->checkMetasLoaded();
		return isset($this->metas[$name]) ? $this->metas[$name] : null;
	}

	/**
	 * @api
	 * @param string $name
	 * @param mixed|null $value
	 */
	public function setMeta($name, $value)
	{
		$this->checkMetasLoaded();
		if ($value === null)
		{
			if (isset($this->metas[$name]))
			{
				unset($this->metas[$name]);
				$this->modifiedMetas = true;
			}
		}
		elseif (!isset($this->metas[$name]) || $this->metas[$name] != $value)
		{
			$this->metas[$name] = $value;
			$this->modifiedMetas = true;
		}
	}
	
	// Correction Method

	/**
	 * @return \Change\Documents\CorrectionFunctions
	 */
	public function getCorrectionFunctions()
	{
		if ($this->correctionFunctions === null)
		{
			$this->correctionFunctions = new CorrectionFunctions($this);
		}
		return $this->correctionFunctions;
	}

	// Generic Method

	/**
	 * @param string|null $treeName
	 */
	public function setTreeName($treeName)
	{
		return;
	}

	/**
	 * @api
	 * @return string|null
	 */
	public function getTreeName()
	{
		return null;
	}
	
	/**
	 * @api
	 * @return \DateTime
	 */
	abstract public function getCreationDate();
	
	/**
	 * @api
	 * @param \DateTime $creationDate
	 */
	abstract public function setCreationDate($creationDate);
	
	/**
	 * @api
	 * @return \DateTime
	 */
	abstract public function getModificationDate();
	
	/**
	 * @api
	 * @param \DateTime $modificationDate
	 */
	abstract public function setModificationDate($modificationDate);
}