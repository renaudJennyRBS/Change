<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\ModelManager
 * @method \Change\Documents\ModelManager getInstance()
 */
class ModelManager extends \Change\AbstractSingleton
{
	protected $publicationStatuses = array('DRAFT','CORRECTION','ACTIVE','PUBLICATED','DEACTIVATED','FILED','DEPRECATED','TRASH','WORKFLOW');
	
	protected $documentModels = array();
	
	protected $modelChildren;
	
	/**
	 * List of Publication status:
	 * DRAFT, CORRECTION, ACTIVE, PUBLICATED, DEACTIVATED, FILED, DEPRECATED, TRASH, WORKFLOW
	 * @return string[]
	 */
	public function getPublicationStatuses()
	{
		return $this->publicationStatuses;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @return string
	 */
	public function composeModelName($moduleName, $documentName)
	{
		return 'modules_' . $moduleName . '/' .$documentName;
	}
	
	/**
	 * @param string $modelName
	 * @return \Change\Documents\AbstractModel|null
	 */
	public function getModelByName($modelName)
	{
		if (!array_key_exists($modelName, $this->documentModels))
		{
			$className = $this->getModelClassName($modelName);
			if ($className)
			{
				$this->documentModels[$modelName] = call_user_func(array($className, 'getNewInstance'));
			}
			else
			{
				$this->documentModels[$modelName] = null;
			}
		}
		return $this->documentModels[$modelName];
	}
	
	/**
	 * @param string $modelName
	 * @return NULL|string
	 */
	protected function getModelClassName($modelName)
	{
		list ($package, $documentName) = explode('/', $modelName);
		list ($packageType, $moduleName) = explode('_', $package);
		if ($packageType != 'modules' || empty($moduleName) || empty($documentName))
		{
			return null;
		}
		$className = $moduleName .'_persistentdocument_'.$documentName.'model';
		if (class_exists($className))
		{
			return $className;
		}
		return null;
	}
	
	/**
	 * @return \Change\Documents\AbstractModel[]
	 */
	public function getModels()
	{
		$this->documentModels = array();
		foreach ($this->getModelNamesByModules() as $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				$model = $this->getModelByName($modelName);
			}
		}
		return $this->documentModels;
	}
	
	/**
	 * returns an array of the type : array('moduleA' => array('modules_moduleA/doc1', ...), ...);
	 * @return array
	 */
	public function getModelNamesByModules()
	{
		//TODO Old class Usage
		return unserialize(file_get_contents(\f_util_FileUtils::buildChangeBuildPath('documentmodels.php')));
	}
	
	/**
	 * If no child is available for model, key does not exists in returned array
	 * @return array array('modules_moduleA/doc1' => array('modules_moduleA/doc2', ...), ...)
	 */
	public static function getChildrenModelNames($modelName = null)
	{
		if ($this->modelChildren === null)
		{
			//TODO Old class Usage
			$this->modelChildren = unserialize(file_get_contents(\f_util_FileUtils::buildChangeBuildPath('documentmodelschildren.php')));
		}
		
		if ($modelName === null)
		{
			return $this->modelChildren;
		}
		
		if (isset($this->modelChildren[$modelName]))
		{
			return $this->modelChildren[$modelName];
		}	
		return array();
	}
}