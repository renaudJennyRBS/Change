<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\Compiler
 */
class Compiler
{
	/**
	 * \Change\Documents\Generators\Model[]
	 */
	protected $models = array();
	
	/**
	 * @var array
	 */
	protected $modelNamesByExtendLevel = array();
	
	/**
	 * @var \Change\Application
	 */
	protected $application;
	
	/**
	 * @param \Change\Application $application
	 */
	public function __construct(\Change\Application $application)
	{
		$this->application = $application;
	}
	
	/**
	 * @param string $vendor
	 * @param string $moduleName
	 * @param string $documentName
	 * @param string $definitionPath
	 * @return \Change\Documents\Generators\Model
	 * @throws \Exception
	 */
	public function loadDocument($vendor, $moduleName, $documentName, $definitionPath)
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		if (is_readable($definitionPath) && $doc->load($definitionPath))
		{
			$model = new \Change\Documents\Generators\Model($vendor, $moduleName, $documentName);
			$model->setXmlDocument($doc);
			$this->addModel($model);
		}
		else
		{
			throw new \RuntimeException('Unable to load document definition : ' . $definitionPath);
		}
		return $model;
	}
	
	/**
	 * @throws \Exception
	 */
	public function buildTree()
	{
		$injectionArray = array();
		foreach ($this->models as $model)
		{
			
			/* @var $model \Change\Documents\Generators\Model */
			
			$model->validate();
			
			$modelName = $model->getName();
			$extendName = $model->getExtend();
			
			
			if ($extendName)
			{
				$extModel = $this->getModelByName($extendName);
				if ($extModel === null)
				{
					throw new \Exception('Document ' . $modelName . ' extend unknow ' . $model->getExtend(). ' document.');
				}
				$model->setExtendModel($extModel);
				$model->setParent($extModel);
				if ($model->getInject())
				{
					if (isset($injectionArray[$extendName]))
					{
						throw new \Exception('Duplicate Injection on ' . $modelName . ' for ' . $extendName. ' Already Injected by ' . $injectionArray[$extendName]);
					}
					$injectionArray[$extendName] = $model;
				}
			}
			elseif ($model->getInject())
			{
				throw new \Exception('Invalid Injection on ' . $modelName . ' document.');
			}
		}
		
		foreach ($this->models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$extModel = $model->getExtendModel();
			if ($extModel)
			{
				$extendName = $extModel->getName();
				
				if (in_array($extModel, $injectionArray))
				{
					throw new \Exception($model . ' extends a injecting model ' . $extendName);
				}
				
				if (isset($injectionArray[$extendName]) && $injectionArray[$extendName] !== $model)
				{
					$model->setParent($injectionArray[$extModel->getName()]);
				}
			}
		}
			
		$this->modelNamesByExtendLevel = array();
		foreach ($this->models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$nbAncestor = count($model->getAncestors());
			$this->modelNamesByExtendLevel[$nbAncestor][] = $model->getName(); 
		}		
		ksort($this->modelNamesByExtendLevel);
	}
	
	/**
	 * 
	 */
	public function validateInheritance()
	{
		foreach ($this->modelNamesByExtendLevel as $lvl => $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				$model = $this->getModelByName($modelName);
				
				$model->validateInheritance();
				
				//Add Inverse Properties
				foreach ($model->getProperties() as $property)
				{
					/* @var $property \Change\Documents\Generators\Property */
					if ($property->hasRelation())
					{
						$docType = $property->getDocumentType();
						if ($docType)
						{
							$im = $this->getModelByName($docType);
							if (!$im)
							{
								throw new \Exception('Inverse Property on unknow Model ' . $docType . ' (' . $modelName . '::' . $property->getName() . ')');
							}
							$ip = new InverseProperty($im, $property);
							$im->addInverseProperty($ip);
						}
					}
				}
			}
		}
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function cleanModelName($name)
	{
		return $name;
	}
	
	/**
	 * @param string $fullName
	 * @return \Change\Documents\Generators\Model|null
	 */
	public function getModelByName($fullName)
	{
		$name = $this->cleanModelName($fullName);
		return isset($this->models[$name]) ? $this->models[$name] : null;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 */
	public function addModel(\Change\Documents\Generators\Model $model)
	{
		$this->models[$this->cleanModelName($model->getName())] = $model;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Model|null
	 */
	public function getParent($model)
	{
		if ($model->getExtend())
		{
			return $this->getModelByName($model->getExtend());
		}
		return null;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Model[]
	 * @throws \Exception
	 */	
	public function getAncestors($model)
	{
		$result = array();
		while (($model = $this->getParent($model)) !== null)
		{
			$modelName = $model->getName();
			if (isset($result[$modelName]))
			{
				throw new \Exception('Recursion on ' . $modelName . ' document.');
			}
			$result[$modelName] = $model;
		}
		return array_reverse($result, true);
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Model[]
	 */
	public function getChildren($model)
	{
		$result = array();
		foreach ($this->models as $cm)
		{
			/* @var $cm \Change\Documents\Generators\Model */
			$cmp = $cm->getExtend() ? $this->getModelByName($cm->getExtend()) : null;
			if ($cmp === $model)
			{
				$result[$cm->getName()] = $cm;
			}
		}
		return $result;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Model[]
	 */
	public function getDescendants($model, $excludeInjected = false)
	{
		$result = array();
		foreach ($this->getChildren($model) as $name => $cm)
		{
			/* @var $cm \Change\Documents\Generators\Model */
			if ($excludeInjected && $cm->getInject())
			{
				continue;
			}
			$result[$name] = $cm;
			$dm = $this->getDescendants($cm);
			if (count($dm))
			{
				$result = array_merge($result, $dm);
			}
		}
		return $result;
	}
	
	/**
	 * @return \Change\Documents\Generators\Model[]
	 */
	public function getModels()
	{
		return $this->models;
	}
	
	/**
	 * @return \Change\Documents\Generators\Model[]
	 */
	public function getModelsByLevel($level = 0)
	{
		$models = array();
		if (isset($this->modelNamesByExtendLevel[$level]))
		{
			$models = array();
			foreach ($this->modelNamesByExtendLevel[$level] as $fullName)
			{
				$models[] = $this->getModelByName($fullName);
			}
		}
		return $models;
	}
	
	
	public function saveModelsPHPCode()
	{
		$compilationPath = $this->application->getWorkspace()->compilationPath();
		
		foreach ($this->models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$generator = new ModelClass();
			$generator->savePHPCode($this, $model, $compilationPath);
			
			$generator = new AbstractDocumentClass();
			$generator->savePHPCode($this, $model, $compilationPath);
			
			if ($model->checkLocalized())
			{
				$generator = new DocumentI18nClass();
				$generator->savePHPCode($this, $model, $compilationPath);
			}
			
			$generator = new AbstractServiceClass();
			$generator->savePHPCode($this, $model, $compilationPath);
		}
		
		$generator = new AbstractDocumentServicesClass();
		$generator->savePHPCode($this, $this->models, $compilationPath);
		
		$generator = new SchemaClass();
		$generator->savePHPCode($this, $this->application->getApplicationServices()->getDbProvider(), $compilationPath);
	}
	
	public function generate()
	{
		$paths = array();
		$workspace = $this->application->getWorkspace();
		if (is_dir($workspace->pluginsModulesPath()))
		{
			$pattern = implode(DIRECTORY_SEPARATOR, array($workspace->pluginsModulesPath(), '*', '*', 'Documents', 'Assets', '*.xml'));
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
		
		if (is_dir($workspace->projectModulesPath()))
		{
			$pattern = implode(DIRECTORY_SEPARATOR, array($workspace->projectModulesPath(), '*', '*', 'Documents', 'Assets', '*.xml'));
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
		
		$nbModels = 0;
		foreach ($paths as $definitionPath)
		{
			$parts = explode(DIRECTORY_SEPARATOR, $definitionPath);
			$count = count($parts);
			$documentName = basename($parts[$count - 1], '.xml');
			$moduleName = $parts[$count - 4];
			$vendor = $parts[$count - 5];
			$this->loadDocument($vendor, $moduleName, $documentName, $definitionPath);
			$nbModels++;
		}
		
		$this->buildTree();
		
		$this->validateInheritance();
		
		$this->saveModelsPHPCode();
	}
}