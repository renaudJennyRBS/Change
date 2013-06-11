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
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param \Change\Application $application
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Application $application, \Change\Application\ApplicationServices $applicationServices)
	{
		$this->application = $application;
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @param string $vendor
	 * @param string $moduleName
	 * @param string $documentName
	 * @param string $definitionPath
	 * @throws \RuntimeException
	 * @return Model
	 */
	public function loadDocument($vendor, $moduleName, $documentName, $definitionPath)
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		if (is_readable($definitionPath) && $doc->load($definitionPath))
		{
			$model = new Model($vendor, $moduleName, $documentName);
			$model->setXmlDocument($doc);
			$this->addModel($model);
		}
		else
		{
			throw new \RuntimeException('Unable to load document definition : ' . $definitionPath, 54001);
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
			
			/* @var $model Model */
			
			$model->validate();
			
			$modelName = $model->getName();
			$extendName = $model->getExtends();

			if ($extendName)
			{
				$extModel = $this->getModelByName($extendName);
				if ($extModel === null)
				{
					throw new \RuntimeException('Document ' . $modelName . ' extend unknown ' . $model->getExtends(), 54002);
				}
				$model->setExtendedModel($extModel);
				$model->setParent($extModel);
				if ($model->getReplace())
				{
					if (isset($injectionArray[$extendName]))
					{
						throw new \RuntimeException('Duplicate Injection on ' . $modelName . ' for ' . $extendName. ' Already Injected by ' . $injectionArray[$extendName], 54003);
					}
					$injectionArray[$extendName] = $model;
					$extModel->replacedBy($model->getName());
				}
			}
			elseif ($model->getReplace())
			{
				throw new \RuntimeException('Invalid Injection on ' . $modelName, 54004);
			}
		}
		
		foreach ($this->models as $model)
		{
			/* @var $model Model */
			$extModel = $model->getExtendedModel();
			if ($extModel)
			{
				$extendName = $extModel->getName();
				
				if (in_array($extModel, $injectionArray))
				{
					throw new \RuntimeException($model . ' extends a "replace" model ' . $extendName, 54005);
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
			/* @var $model Model */
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

				if ($model->checkStateless())
				{
					continue;
				}

				//Add Inverse Properties
				foreach ($model->getProperties() as $property)
				{
					/* @var $property \Change\Documents\Generators\Property */
					if (!$property->getStateless() && $property->hasRelation())
					{
						$docType = $property->getDocumentType();
						if ($docType)
						{
							$im = $this->getModelByName($docType);
							if (!$im)
							{
								throw new \RuntimeException('Inverse Property on unknown Model ' . $docType . ' (' . $modelName . '::' . $property->getName() . ')', 54006);
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
	 * @return Model|null
	 */
	public function getModelByName($fullName)
	{
		$name = $this->cleanModelName($fullName);
		return isset($this->models[$name]) ? $this->models[$name] : null;
	}
	
	/**
	 * @param Model $model
	 */
	public function addModel(Model $model)
	{
		$this->models[$this->cleanModelName($model->getName())] = $model;
	}
	
	/**
	 * @param Model $model
	 * @return Model|null
	 */
	public function getParent($model)
	{
		if ($model->getExtends())
		{
			return $this->getModelByName($model->getExtends());
		}
		return null;
	}
	
	/**
	 * @param Model $model
	 * @return Model
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
				throw new \RuntimeException('Recursion on ' . $modelName, 54007);
			}
			$result[$modelName] = $model;
		}
		return array_reverse($result, true);
	}
	
	/**
	 * @param Model $model
	 * @return Model
	 */
	public function getChildren($model)
	{
		$result = array();
		foreach ($this->models as $cm)
		{
			/* @var $cm Model */
			$cmp = $cm->getExtends() ? $this->getModelByName($cm->getExtends()) : null;
			if ($cmp === $model)
			{
				$result[$cm->getName()] = $cm;
			}
		}
		return $result;
	}

	/**
	 * @param Model $model
	 * @param boolean $excludeInjected
	 * @return Model
	 */
	public function getDescendants($model, $excludeInjected = false)
	{
		$result = array();
		foreach ($this->getChildren($model) as $name => $cm)
		{
			/* @var $cm Model */
			if ($excludeInjected && $cm->getReplace())
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
	 * @return Model
	 */
	public function getModels()
	{
		return $this->models;
	}

	/**
	 * @param integer $level
	 * @return Model
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

		$generator = new ModelsNamesClass();
		$generator->savePHPCode($this, $this->models, $compilationPath);

		$generator = new TreeNamesClass();
		$generator->savePHPCode($this, $this->models, $compilationPath);

		foreach ($this->models as $model)
		{
			/* @var $model Model */
			$generator = new ModelClass();
			$generator->savePHPCode($this, $model, $compilationPath);
			
			$generator = new BaseDocumentClass();
			$generator->savePHPCode($this, $model, $compilationPath);
			
			if ($model->checkLocalized())
			{
				$generator = new DocumentLocalizedClass();
				$generator->savePHPCode($this, $model, $compilationPath);
			}
		}

		$generator = new SchemaClass();
		$generator->savePHPCode($this, $this->applicationServices->getDbProvider(), $compilationPath);
	}
	
	public function generate()
	{
		$nbModels = 0;
		$plugins = $this->applicationServices->getPluginManager()->getModules();
		foreach($plugins as $plugin)
		{
			$vendor = $plugin->getVendor();
			$moduleName = $plugin->getShortName();
			foreach ($plugin->getDocumentDefinitionPaths() as $documentName => $definitionPath)
			{
				$this->loadDocument($vendor, $moduleName, $documentName, $definitionPath);
				$nbModels++;
			}
		}
		
		$this->buildTree();
		$this->validateInheritance();
		if (is_array($this->models) && count($this->models))
		{
			$this->saveModelsPHPCode();
		}
	}
}