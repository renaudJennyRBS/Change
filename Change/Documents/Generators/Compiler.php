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
	
	protected $modelNamesByExtendLevel = array();
	
	/**
	 * @var string
	 */
	protected $injection = array();
	
	/**
	 * @return \Change\Documents\Generators\Model
	 */
	public function getDefaultModel()
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load(__DIR__ . '/Assets/document.xml');
		$model = new \Change\Documents\Generators\Model(null, null);
		$model->setXmlDocument($doc);
		return $model;
	}
	
	public function loadProjectDocuments()
	{
		$pathFilter = \Change\Stdlib\Path::projectPath('modules', '*', 'persistentdocument', '*.xml');
		$fileNames = glob($pathFilter);
		foreach ($fileNames as $fileName)
		{
			$documentName = basename($fileName, '.xml');
			$moduleName = basename(dirname(dirname($fileName)));
			$doc = new \DOMDocument('1.0', 'utf-8');
			if ($doc->load($fileName))
			{
				$model = new \Change\Documents\Generators\Model($moduleName, $documentName);
				$model->setXmlDocument($doc);
				if ($model->getExtend() === null)
				{
					$model->applyDefault($this->getDefaultModel());
				}
				$this->addModel($model);
			}
			else
			{
				throw new \Exception('Unable to load document definition : ' . $fileName);
			}
		}
		
		$this->checkExtends();
		$this->buildDependencies();
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 */
	public function addModel(\Change\Documents\Generators\Model $model)
	{
		$this->models[$model->getFullName()] = $model;
	}
	
	/**
	 * @throws \Exception
	 */
	public function checkExtends()
	{
		foreach ($this->models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if ($model->getExtend())
			{
				if (!isset($this->models[$model->getExtend()]))
				{
					throw new \Exception('Document ' . $model->getFullName() . ' extend unknow ' . $model->getExtend(). ' document.');
				}
				
				if ($model->getInject())
				{
					if (isset($this->injection[$model->getExtend()]))
					{
						throw new \Exception('Duplicate Injection on ' . $model->getFullName() . ' for ' . $model->getExtend(). ' Already Injected by ' . $this->injection[$model->getExtend()]);
					}
					$this->injection[$model->getExtend()] = $model->getFullName();
				}
			}
			elseif ($model->getInject())
			{
				throw new \Exception('Invalid Injection on ' . $model->getFullName() . ' document.');
			}
		}
		$this->modelNamesByExtendLevel = array();
		foreach ($this->models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$ancestors = $this->getAncestors($model);
			$this->modelNamesByExtendLevel[count($ancestors)][] = $model->getFullName(); 
		}
		ksort($this->modelNamesByExtendLevel);
	}
	
	/**
	 * 
	 */
	public function buildDependencies()
	{
		$injectedModelNames = array_flip($this->injection);
		foreach ($this->modelNamesByExtendLevel as $lvl => $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				$model = $this->getModelByFullName($modelName);
				if ($model->getInject()) //Check Injection
				{
					if (count($this->getChildren($model)))
					{
						throw new \Exception('Injected Model ' . $model->getFullName() . ' has children.');
					}
				}
				
				$ancestors =  array_reverse($this->getAncestors($model));
				if ($model->getUseCorrection())
				{
					if (!$model->getUseCorrectionByAncestors($ancestors))
					{
						$model->addCorrectionProperties();
					}
				}
				
				if (count($model->getSerializedproperties()))
				{
					if (!$model->getHasSerializedPropertiesByAncestors($ancestors))
					{
						$model->addS18sProperty();
					}
				}
				
				foreach ($model->getProperties() as $property)
				{
					$ap = $model->getPropertyByAncestors($ancestors, $property->getName());
					if ($ap)
					{
						$property->setOverride(true);
					}
					
					/* @var $property \Change\Documents\Generators\Property */
					if ($property->getInverse())
					{
						$docType = ($property->getDocumentType()) ? $property->getDocumentType() : $model->getDocumentTypeByAncestors($ancestors, $property->getName());
						$im = $this->getModelByFullName($docType);
						if (!$im)
						{
							throw new \Exception('Inverse Property on unknow Model ' . $property->getDocumentType() . ' (' . $model->getFullName() . '::' . $property->getName() . ')');
						}
						$ip = new InverseProperty($property, $model);
						$model->addInverseProperty($ip);
					}
					
					if ($property->getLocalized())
					{
						$model->setLocalized(true);
					}
				}
				
				if ($model->getLocalized())
				{
					$model->makeLocalised($ancestors);
				}
			}
		}

	}
	
	public function getModelByFullName($fullName)
	{
		return isset($this->models[$fullName]) ? $this->models[$fullName] : null;
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
			if (isset($result[$model->getFullName()]))
			{
				throw new \Exception('Recursion On ' . $model->getFullName() . ' document.');
			}
			$result[$model->getFullName()] = $model;
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
			if ($cm->getExtend() === $model->getFullName())
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
			
	
	public function saveModelsPHPCode()
	{
		foreach ($this->models as $model)
		{
			$generator = new ModelClass();
			$generator->savePHPCode($this, $model);
		}
	}
}