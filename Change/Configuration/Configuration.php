<?php
namespace Change\Configuration;

use \Zend\Stdlib\ErrorHandler;
use Zend\Json\Json;

/**
 * @name \Change\Configuration\Configuration
 */
class Configuration
{
	const CONFIGURATION_REFRESHED_EVENT = "\Change\Configuration\Configuration::refresh";

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
	protected $config = null;

	/**
	 * @var array
	 */
	protected $define = null;

	/**
	 * @return boolean
	 */
	protected function isCompiled()
	{
		return file_exists($this->getCompiledConfigPath());
	}

	/**
	 */
	protected function compile()
	{
		// Compile new config and defines.
		$config = $this->mergeJsonConfigurations();
		$configs = $config['config'];
		$defines = $this->fixDefinesArray($config['defines']);
		// Save compiled file.
		$content = "<?php\n// \\Change\\Configuration\\Configuration::setDefineArray PART // \n";
		$content .= '$configuration->setDefineArray(' . var_export($defines, true) . ");\n\n";
		if (isset($defines['DEVELOPMENT_MODE']) && $defines['DEVELOPMENT_MODE'])
		{
			$this->buildDevelopmentDefineFile($defines);
		}
		$content .= "// \\Change\\Configuration\\Configuration::setConfigArray PART // \n";
		$content .= '$configuration->setConfigArray(' . var_export($configs, true) . ');';
		\Change\Stdlib\File::write($this->getCompiledConfigPath(), $content);
		return array("config" => $configs, "defines" => $defines);
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
	 */
	protected function load()
	{
		// If specific environnement add a dot to complet in path file
		$this->config = array();
		$this->define = array();
		if (!$this->isCompiled())
		{
			$this->compile();
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
		// base config
		$configFiles = $this->application->getWorkspace()->getProjectConfigurationPaths();
		$configProjectPath = $configFiles[0];

		if (empty($entryName) || ($value !== null && !is_string($value)))
		{
			throw new \InvalidArgumentException("Value should be a string and entry name non empty (value = $value, entryName = $entryName)");
		}
		$configData = \Change\Stdlib\File::read($configProjectPath);
		$overridableConfig = Json::decode($configData, Json::TYPE_ARRAY);

		$entry = array('config' => array());
		$item = &$entry['config'];
		foreach (explode('/', $path) as $name)
		{
			$trimmedName = trim($name);
			if ($trimmedName != '')
			{
				if (!isset($item[$trimmedName]))
				{
					$item[$trimmedName] = array();
				}
				$item = &$item[$trimmedName];
			}
		}
		$item[$entryName] = $value;
		$mergedConfig = \Zend\Stdlib\ArrayUtils::merge($overridableConfig, $entry);
		\Change\Stdlib\File::write($configProjectPath, Json::encode($mergedConfig));
		$oldValue = $this->getEntry($path);
		$this->clear();
		$this->load();
		return $oldValue;
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
		return $this->define;
	}

	/**
	 * @param array $defines
	 */
	public function setDefineArray($defines)
	{
		$this->define = $defines;
	}

	/**
	 * Setup constants.
	 */
	protected function applyDefines()
	{
		foreach ($this->define as $name => $value)
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
	 *
	 * @throws \RuntimeException
	 * @return string
	 */
	protected function mergeJsonConfigurations()
	{
		$configData = \Change\Stdlib\File::read(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Assets', 'project.json')));
		$config = Json::decode($configData, Json::TYPE_ARRAY);

		foreach ($this->application->getWorkspace()->getProjectConfigurationPaths() as $path)
		{
			$data = \Change\Stdlib\File::read($path);
			$projectConfig = Json::decode($data, Json::TYPE_ARRAY);
			$config = \Zend\Stdlib\ArrayUtils::merge($config, $projectConfig);
		}

		switch ($config['config']['logging']['level'])
		{
			case 'EXCEPTION' :
			case 'ALERT' :
				$logLevel = 'ALERT';
				break;
			case 'ERROR' :
			case 'ERR' :
				$logLevel = 'ERR';
				break;
			case 'NOTICE' :
				$logLevel = 'NOTICE';
				break;
			case 'DEBUG' :
				$logLevel = 'DEBUG';
				break;
			case 'INFO' :
				$logLevel = 'INFO';
				break;
			default :
				$logLevel = 'WARN';
				break;
		}
		$config['config']['logging']['level'] = $logLevel;
		foreach (array('TMP_PATH' , 'DEFAULT_HOST', 'PROJECT_ID', 'PHP_CLI_PATH', 'DEVELOPMENT_MODE') as $requiredConfigEntry)
		{
			if (!isset($config['defines'][$requiredConfigEntry]))
			{
				throw new \RuntimeException('Please define ' . $requiredConfigEntry . ' in your profile configuration file');
			}
		}
		return $config;
	}

	/**
	 * @param array $configDefineArray
	 * @return array
	 */
	protected function fixDefinesArray($configDefineArray)
	{
		foreach ($configDefineArray as $name => $value)
		{
			if (is_string($value))
			{
				// Match PROJECT_HOME . DIRECTORY_SEPARATOR . 'config'
				// Or CHANGE_CONFIG_DIR . 'toto'
				// But not Fred's Directory
				if (preg_match('/^(([A-Z][A-Z_0-9]+)|(\'[^\']*\'))(\s*\.\s*(([A-Z][A-Z_0-9]+)|(\'[^\']*\')))+$/', $value))
				{
					$configDefineArray[$name] = 'return ' . $value . ';';
				}
				elseif ($value === 'true')
				{
					$configDefineArray[$name] = true;
				}
				elseif ($value === 'false')
				{
					$configDefineArray[$name] = false;
				}
				elseif (is_numeric($value))
				{
					$configDefineArray[$name] = floatval($value);
				}
			}
		}
		return $configDefineArray;
	}

	/**
	 * @param array $defineArray
	 */
	protected function buildDevelopmentDefineFile($defineArray)
	{
		$content = "<?php // For IDE completion only //" . PHP_EOL;
		$content .= "throw new Exception('Do not include this file');" . PHP_EOL;
		foreach ($defineArray as $key => $value)
		{
			$defval = var_export($value, true);
			if (is_string($value))
			{
				if (strpos($value, 'return ') === 0 && substr($value, -1) === ';')
				{
					$defval = substr($value, 7, strlen($value) - 8);
				}
			}
			$content .= "define('" . $key . "', " . $defval . ");" . PHP_EOL;
		}
		\Change\Stdlib\File::write($this->getCompiledDefinesPath(), $content);
	}

	/**
	 * Clears the config
	 */
	protected function clear()
	{
		if (file_exists($this->getCompiledConfigPath()))
		{
			ErrorHandler::start();
			unlink($this->getCompiledConfigPath());
			ErrorHandler::stop(true);
		}
		$this->config = array();
		$this->define = array();
	}

	/**
	 * Refresh compiled configuration informations - raises a CONFIGURATION_REFRESHED_EVENT event
	 *
	 * @api
	 */
	public function refresh()
	{
		$oldDefine = $this->define;
		$oldConfig = $this->config;
		$this->clear();
		$this->load();
		$this->application->getApplicationServices()->getEventManager()->trigger(self::CONFIGURATION_REFRESHED_EVENT, $this, array('oldDefineArray' => $oldDefine, 'oldConfigArray' => $oldConfig));
	}

}