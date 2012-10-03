<?php
namespace Change\Application;

/**
 * @name \Change\Application\Configuration
 */
class Configuration
{
	/**
	 * @var string
	 */
	protected $compiledFile;
	
	/**
	 * @param string $compiledFile
	 */
	public function __construct($compiledFile = null)
	{
		$this->load($compiledFile);
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
		return is_file($this->compiledFile);
	}
	
	/**
	 * Load the configuration, using the php file auto compiled in Compilation/Config.
	 * 
	 * @param string $compiledFile
	 */
	public function load($compiledFile)
	{
		// If specific environnement add a dot to complet in path file
		$this->config = array();
		$this->defines = array();
		
		if ($compiledFile)
		{
			$this->compiledFile = $compiledFile;
			if (!$this->isCompiled())
			{
				throw new \Exception("Could not find $compiledFile. You must compile your configuration.");
			}
			$configuration = $this;
			include $compiledFile;
			$this->applyDefines();
		}
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
	 * @param string $path
	 * @param string $entryName
	 * @param string $value
	 * @return string | false The old value or false if the operation failed.
	 */
	public function addPersistentEntry($path, $entryName, $value)
	{
		if (empty($entryName) || ($value !== null && !is_string($value)))
		{
			return false;
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
			return false;
		}
		
		$this->addVolatileEntry($path, $value);
		$generator = new \Change\Application\Configuration\Generator();
		return $generator->addPersistentEntry($path, $entryName, $value);
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
}