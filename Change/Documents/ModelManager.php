<?php
namespace Change\Documents;

use Change\Stdlib\File;

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

	/**
	 * @var string[]
	 */
	protected $modelsNames = null;

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices = null;
	
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

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param $pluginName
	 * @param $shortModelName
	 */
	public function initializeModel($vendorName, $moduleName, $shortModelName)
	{
		$pm = $this->getApplicationServices()->getPluginManager();
		$module = $pm->getModule($vendorName, $moduleName);
		if ($module === null)
		{
			throw new \InvalidArgumentException('Module ' . $vendorName  . '_' . $moduleName . ' does not exist', 999999);
		}
		$normalizedShortModelName = $this->normalizeModelName($shortModelName);
		$docPath = implode(DIRECTORY_SEPARATOR, array($module->getBasePath(), 'Documents', 'Assets', $normalizedShortModelName . '.xml'));
		if (file_exists($docPath))
		{
			throw new \RuntimeException('Model file already exists at path ' . $docPath, 999999);
		}
		File::write($docPath, File::read(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'Sample.xml'));
		return $docPath;
	}

	/**
	 * @param $pluginName
	 * @param $shortModelName
	 */
	public function initializeFinalDocumentPhpClass($vendorName, $moduleName, $shortModelName)
	{
		$pm = $this->getApplicationServices()->getPluginManager();
		$module = $pm->getModule($vendorName, $moduleName);
		if ($module === null)
		{
			throw new \InvalidArgumentException('Module ' . $vendorName  . '_' . $moduleName . ' does not exist', 999999);
		}
		$normalizedShortModelName = $this->normalizeModelName($shortModelName);
		$docPath = implode(DIRECTORY_SEPARATOR, array($module->getBasePath(), 'Documents', $normalizedShortModelName . '.php'));
		if (file_exists($docPath))
		{
			throw new \RuntimeException('Final PHP Document file already exists at path ' . $docPath, 999999);
		}
		$attributes = array('vendor' => $vendorName, 'module' => $moduleName, 'name' => $normalizedShortModelName);
		$loader = new \Twig_Loader_Filesystem(__DIR__);
		$twig = new \Twig_Environment($loader);
		File::write($docPath, $twig->render('Assets/Sample.php.twig', $attributes));
		return $docPath;
	}

	/**
	 * @param $name
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function normalizeModelName($name)
	{
		$ucfName = ucfirst($name);
		if (!preg_match('/^[A-Z][a-zA-Z0-9]{1,24}$/', $ucfName))
		{
			throw new \InvalidArgumentException('Model name should match ^[A-Z][a-zA-Z0-9]{1,24}$', 999999);
		}
		return $ucfName;
	}
}