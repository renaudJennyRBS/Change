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
	 * @var string
	 */
	protected $injection = array();
	
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
	 * @return \Change\Documents\Generators\Model
	 */
	public function getDefaultModel()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/Assets/document.xml');
		$model = new \Change\Documents\Generators\Model(null, null, null);
		$model->setXmlDocument($doc);
		return $model;
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
			throw new \Exception('Unable to load document definition : ' . $definitionPath);
		}
		return $model;
	}
	
	/**
	 * @throws \Exception
	 */
	public function checkExtends()
	{
		$this->injection = array();
		
		foreach ($this->models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$modelName = $model->getFullName();
			
			if ($model->getExtend())
			{
				$extModel = $this->getModelByFullName($model->getExtend());
				if ($extModel === null)
				{
					throw new \Exception('Document ' . $modelName . ' extend unknow ' . $model->getExtend(). ' document.');
				}
				$extendName = $extModel->getFullName();
				if ($model->getInject())
				{
					if (isset($this->injection[$extendName]))
					{
						throw new \Exception('Duplicate Injection on ' . $modelName . ' for ' . $extendName. ' Already Injected by ' . $this->injection[$extendName]);
					}
					$this->injection[$extendName] = $modelName;
					$model->applyInjection($extModel);
				}
			}
			elseif ($model->getInject())
			{
				throw new \Exception('Invalid Injection on ' . $modelName . ' document.');
			}
		}
		
		$this->modelNamesByExtendLevel = array();
		
		foreach ($this->models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$nbAncestor = count($this->getAncestors($model));
			$this->modelNamesByExtendLevel[$nbAncestor][] = $this->cleanModelName($model->getFullName()); 
			if ($nbAncestor === 0)
			{
				$model->applyDefault($this->getDefaultModel());
			}
		}
		
		ksort($this->modelNamesByExtendLevel);
	}
	
	/**
	 * 
	 */
	public function buildDependencies()
	{
		$this->checkExtends();
		foreach ($this->modelNamesByExtendLevel as $lvl => $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				$model = $this->getModelByFullName($modelName);
				$ancestors =  $this->getAncestors($model);
				$model->validate($ancestors);
				
				//Add Inverse Properties
				foreach ($model->getProperties() as $property)
				{
					/* @var $property \Change\Documents\Generators\Property */
					if ($property->getInverse())
					{
						$docType = $property->getDocumentType();
						$im = $this->getModelByFullName($docType);
						if (!$im)
						{
							throw new \Exception('Inverse Property on unknow Model ' . $docType . ' (' . $modelName . '::' . $property->getName() . ')');
						}
						$ip = new InverseProperty($property, $model);
						$im->addInverseProperty($ip);
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
		return strtolower(str_replace(array('/', 'modules_'), array('_', 'Change_'), $name));
	}
	
	/**
	 * @param string $fullName
	 * @return \Change\Documents\Generators\Model|null
	 */
	public function getModelByFullName($fullName)
	{
		$name = $this->cleanModelName($fullName);
		return isset($this->models[$name]) ? $this->models[$name] : null;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 */
	public function addModel(\Change\Documents\Generators\Model $model)
	{
		$this->models[$this->cleanModelName($model->getFullName())] = $model;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Model|null
	 */
	public function getParent($model)
	{
		if ($model->getExtend())
		{
			return $this->getModelByFullName($model->getExtend());
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
			$modelName = $model->getFullName();
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
			$cmp = $cm->getExtend() ? $this->getModelByFullName($cm->getExtend()) : null;
			if ($cmp === $model)
			{
				$result[$cm->getFullName()] = $cm;
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
				$models[] = $this->getModelByFullName($fullName);
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
			
			if ($model->getLocalized())
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
		$generator->savePHPCode($this, $this->application->getApplicationServices()->getDbProvider()->getSchemaManager(), $compilationPath);
	}
}