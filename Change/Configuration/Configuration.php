<?php
namespace Change\Configuration;

use \Zend\Stdlib\ErrorHandler;

/**
 * @name \Change\Configuration\Configuration
 */
class Configuration
{	
	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * Build the configuration for the given Change Application 
	 * @param \Change\Application $application
	 */
	public function __construct(\Change\Application $application)
	{
		$this->application = $application;
		$this->load();
	}
	
	/**
	 * The compiled project config.
	 * 
	 * @var array
	 */
	private $config = null;
	
	/**
	 * @var array
	 */
	private $defines = null;
	
	/**
	 * @return boolean
	 */
	public function isCompiled()
	{
		return file_exists($this->getCompiledConfigPath()); 
	}
	
	/**
	 * @return string
	 */
	protected function getCompiledConfigPath()
	{
		return $this->application->getApplicationServices()->getWorkspace()->compilationPath('Config', 'project.php');
	}
	
	/**
	 * @return string
	 */
	protected function getCompiledDefinesPath()
	{
		return $this->application->getApplicationServices()->getWorkspace()->compilationPath('Config', 'dev_defines.php');
	}
		
	/**
	 * Load the configuration, using the php file auto compiled in Compilation/Config. 
	 * If no compiled config, load the bootstrap config.
	 */
	public function load()
	{
		// If specific environnement add a dot to complet in path file
		$this->config = array();
		$this->defines = array();
		if (!$this->isCompiled())
		{
			$this->getGenerator()->compile();
		}
		$configuration = $this;
		include $this->getCompiledConfigPath();
		$this->applyDefines();
	}
	
	/**
	 * @param string $path 
	 * @return boolean
	 */
	public function hasEntry($path)
	{
		$current = $this->config;
		foreach (explode('/', $path) as $part)
		{
			if (!isset($current[$part]))
			{
				return false;
			}
			$current = $current[$part];
		}
		return true;
	}
	
	/**
	 * @param string $path
	 * @param string $defaultValue
	 * @return mixed | null
	 */
	public function getEntry($path, $defaultValue = null)
	{
		$current = $this->config;
		foreach (explode('/', $path) as $part)
		{
			if (!isset($current[$part]))
			{
				return $defaultValue;
			}
			$current = $current[$part];
		}
		return $current;
	}
	
	/**
	 * @param string $path
	 * @param string $defaultValue
	 * @return boolean
	 */
	public function getBooleanEntry($path, $defaultValue = null)
	{
		return $this->getEntry($path, $defaultValue) === 'true';
	}
	
	/**
	 * @param string $path
	 * @param string $value
	 * @return boolean
	 */
	public function addVolatileEntry($path, $value)
	{
		$sections = array();
		foreach (explode('/', $path) as $name)
		{
			if (trim($name) != '')
			{
				$sections[] = trim($name);
			}
		}
		if (count($sections) < 2)
		{
			return false;
		}
	
		$config = array();
		$sections = array_reverse($sections);
		foreach ($sections as $section)
		{
			if ($section === reset($sections))
			{
				$config = $value;
			}
			$config = array($section => $config);
		}
	
		$this->config = \Zend\Stdlib\ArrayUtils::merge($this->config, $config);
		return true;
	}
	
	/**
	 * Add an entry in the first configuration file returned by \Change\Application::getProjectConfigurationPaths.
	 * 
	 * @api
	 * 
	 * @param string $path
	 * @param string $entryName
	 * @param string $value
	 * @return string The old value
	 */
	public function addPersistentEntry($path, $entryName, $value)
	{
		if (empty($entryName) || ($value !== null && !is_string($value)))
		{
			throw new \InvalidArgumentException("Value should be a string and entry name non empty (value = $value, entryName = $entryName)");
		}
		$pathArray = array('config');
		foreach (explode('/', $path) as $index => $name)
		{
			if (trim($name) != '')
			{
				$pathArray[] = trim($name);
			}
		}
		if (count($pathArray) < 2)
		{
			throw new \InvalidArgumentException('Path must be at least 2-level deep');
		}
		
		$this->addVolatileEntry($path . '/' . $entryName, $value);
		return $this->getGenerator()->addPersistentEntry($pathArray, $entryName, $value);
	}
	
	/**
	 * @return array
	 */
	public function getConfigArray()
	{
		return $this->config;
	}
	
	/**
	 * @param array $config
	 */
	public function setConfigArray($config)
	{
		$this->config = $config;
	}

	/**
	 * @return array
	 */
	public function getDefineArray()
	{
		return $this->defines;
	}
	
	/**
	 * @param array $defines
	 */
	public function setDefineArray($defines)
	{
		$this->defines = $defines;
	}
	
	/**
	 * Setup constants.
	 */
	protected function applyDefines()
	{
		foreach ($this->defines as $name => $value)
		{
			if (!defined($name))
			{
				if (is_string($value))
				{
					if (strpos($value, 'return ') === 0 && substr($value, -1) === ';')
					{
						$value = eval($value);
					}
				}
				define($name, $value);
			}
		}
	}
	
	/**
	 * Clears the config
	 * @api
	 */
	public function clear()
	{
		if (file_exists($this->getCompiledConfigPath()))
		{
			ErrorHandler::start();
			unlink($this->getCompiledConfigPath());
			ErrorHandler::stop(true);
		}
		$this->config = array();
		$this->defines = array();
	}
	
	protected function getGenerator()
	{
		$configFiles = $this->application->getApplicationServices()->getWorkspace()->getProjectConfigurationPaths();
		$bootstrapConfig = $this->application->getBootstrapConfiguration();
		$compiledConfigPath = $this->getCompiledConfigPath();
		$compiledDefinesPath = $this->getCompiledDefinesPath();
		return new \Change\Configuration\Generator($bootstrapConfig, $configFiles, $compiledConfigPath, $compiledConfigPath);
	}
}