<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\ModelManager
 * @method \Change\Documents\ModelManager getInstance()
 */
class ModelManager
{
	/**
	 * @var \Change\Documents\AbstractModel[]
	 */
	protected $documentModels = array();

	protected $modelsNames = null;
	
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
				/* @var $model \Change\Documents\AbstractModel */
				$model = new $className($this);
				$this->documentModels[$modelName] = $model;
			}
			else
			{
				$this->documentModels[$modelName] = null;
			}
		}
		return $this->documentModels[$modelName];
	}

	/**
	 * @return string[]
	 */
	public function getModelsNames()
	{
		if ($this->modelsNames === null)
		{
			$this->modelsNames = new \Compilation\Change\Documents\ModelsNames();
		}
		return $this->modelsNames->getArrayCopy();
	}

	/**
	 * @return string[]
	 */
	public function getVendors()
	{
		if ($this->modelsNames === null)
		{
			$this->modelsNames = new \Compilation\Change\Documents\ModelsNames();
		}
		$vendors = array();
		foreach ($this->modelsNames as $name)
		{
			list($v,,) = explode('_', $name);
			$vendors[$v] = true;
		}
		return array_keys($vendors);
	}

	/**
	 * @param string $vendor
	 * @return string[]
	 */
	public function getShortModulesNames($vendor)
	{
		if ($this->modelsNames === null)
		{
			$this->modelsNames = new \Compilation\Change\Documents\ModelsNames();
		}
		$smn = array();
		foreach ($this->modelsNames as $name)
		{
			list($v,$m,) = explode('_', $name);
			if ($v === $vendor) {$smn[$m] = true;}
		}
		return array_keys($smn);
	}

	/**
	 * @param string $vendor
	 * @param string $shortModuleName
	 * @return string[]
	 */
	public function getShortDocumentsNames($vendor, $shortModuleName)
	{
		if ($this->modelsNames === null)
		{
			$this->modelsNames = new \Compilation\Change\Documents\ModelsNames();
		}

		$sdn = array();
		foreach ($this->modelsNames as $name)
		{
			list($v,$m,$d) = explode('_', $name);
			if ($v === $vendor && $m === $shortModuleName) {$sdn[$d] = true;}
		}
		return array_keys($sdn);
	}
	
	/**
	 * @param string $modelName
	 * @return NULL|string
	 */
	protected function getModelClassName($modelName)
	{
		$parts = explode('_', $modelName);
		if (count($parts) === 3)
		{
			list($vendor, $moduleName, $documentName) = $parts;
			$className = 'Compilation\\' . $vendor . '\\' . $moduleName .'\\Documents\\' . $documentName.'Model';
			if (class_exists($className))
			{
				return $className;
			}
		}
		return null;
	}
}