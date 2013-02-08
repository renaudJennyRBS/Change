<?php
namespace Change\Configuration;

use \Zend\Stdlib\ErrorHandler;
use Zend\Json\Json;

/**
 * @name \Change\Configuration\Configuration
 */
class Configuration
{
	/**
	 * @var string[]
	 */
	protected $configurationFiles = array();
	
	/**
	 * @param $configurationFiles
	 * @param array|null $config
	 */
	public function __construct(array $configurationFiles, $config = null)
	{
		if (count($configurationFiles) == 0)
		{
			throw new \InvalidArgumentException('$configurationFiles must have at least one entry');
		}
		$this->configurationFiles = $configurationFiles;
		if (is_array($config))
		{
			if (!isset($cachedConfig['config']))
			{
				throw new \InvalidArgumentException('$config has to be an array with "config" keys set');
			}
		}
		else
		{
			$config = $this->mergeJsonConfigurations();
		}
		$this->setConfigArray($config['config']);
	}
	
	/**
	 * The compiled project config.
	 *
	 * @var array
	 */
	protected $config = array();
	
	/**
	 * @var array
	 */
	protected $define = array();
	
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
		$current = $this->getConfigArray();
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
	 * @api
	 * @param string $path
	 * @param string $value
	 * @return boolean
	 */
	public function addVolatileEntry($path, $value)
	{
		$update = $this->getAddEntryArrayToMerge($path, $value);
		if (!count($update))
		{
			return false;
		}
		
		$this->config = \Zend\Stdlib\ArrayUtils::merge($this->config, $update);
		return true;
	}
	
	/**
	 * Add an entry in the first configuration file returned by \Change\Application::getProjectConfigurationPaths.
	 * 
	 * @api
	 * @param string $path
	 * @param string $value
	 * @return boolean
	 */
	public function addPersistentEntry($path, $value)
	{
		$update = $this->getAddEntryArrayToMerge($path, $value);
		if (!count($update))
		{
			return false;
		}
		
		// Base config.
		$configProjectPath = $this->configurationFiles[0];
		$overridableConfig = Json::decode(\Change\Stdlib\File::read($configProjectPath), Json::TYPE_ARRAY);
		$mergedConfig = \Zend\Stdlib\ArrayUtils::merge($overridableConfig, array('config' => $update));
		\Change\Stdlib\File::write($configProjectPath, Json::encode($mergedConfig));
		
		// Update loaded config.
		$this->config = \Zend\Stdlib\ArrayUtils::merge($this->config, $update);
		return true;
	}
	
	/**
	 * @param string $path
	 * @param mixed $value
	 * @return array
	 */
	protected function getAddEntryArrayToMerge($path, $value)
	{
		$parts = explode('/', $path);
		if (count($parts) < 2)
		{
			return array();
		}
		$entryName = array_pop($parts);
		
		$entry = array('config' => array());
		$item = &$entry['config'];
		foreach ($parts as $name)
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
		return $entry['config'];
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
	 * @throws \RuntimeException
	 * @return string
	 */
	protected function mergeJsonConfigurations()
	{
		$configData = \Change\Stdlib\File::read(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Assets', 'project.json')));
		$config = Json::decode($configData, Json::TYPE_ARRAY);
		
		foreach ($this->configurationFiles as $path)
		{
			$data = \Change\Stdlib\File::read($path);
			$projectConfig = Json::decode($data, Json::TYPE_ARRAY);
			$config = \Zend\Stdlib\ArrayUtils::merge($config, $projectConfig);
		}
		
		switch ($config['config']['logging']['level'])
		{
			// @codeCoverageIgnoreStart
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
			// @codeCoverageIgnoreEnd
		}
		$config['config']['logging']['level'] = $logLevel;
		
		foreach (array('php-cli-path', 'development-mode') as $requiredConfigEntry)
		{
			if (!isset($config['config']['general'][$requiredConfigEntry]))
			{
				// @codeCoverageIgnoreStart
				throw new \RuntimeException('Please define config/general/' . $requiredConfigEntry . ' in your profile configuration file');
				// @codeCoverageIgnoreEnd
			}
		}
		return $config;
	}
}