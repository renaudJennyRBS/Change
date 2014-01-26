<?php
namespace Change;

/**
 * @name \Change\Application
 * @api
 */
class Application
{
	const CHANGE_VERSION = "4.0";

	/**
	 * @var Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;

	/**
	 * @var bool
	 */
	protected $started = false;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;


	/**
	 * @api
	 * @return string
	 */
	public function getVersion()
	{
		return self::CHANGE_VERSION;
	}

	/**
	 * @param \Zend\Stdlib\Parameters $context
	 */
	public function setContext(\Zend\Stdlib\Parameters $context)
	{
		$this->context = $context;
	}

	/**
	 * @api
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext()
	{
		if ($this->context === null)
		{
			$this->setContext(new \Zend\Stdlib\Parameters());
		}
		return $this->context;
	}

	/**
	 * @return \Composer\Autoload\ClassLoader|null
	 */
	public function registerCoreAutoload()
	{
		$classLoader = require_once PROJECT_HOME . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
		if ($classLoader instanceof \Composer\Autoload\ClassLoader)
		{
			$classLoader->set('Compilation', array(dirname($this->getWorkspace()->compilationPath())));
		}
	}

	/**
	 * Register autoload for plugins
	 */
	public function registerPluginsAutoload()
	{
		$pluginsLoader = new \Change\Plugins\Autoloader();
		$pluginsLoader->setWorkspace($this->getWorkspace());
		$pluginsLoader->register();
	}

	/**
	 * @api
	 * Namespace-based autoloading
	 */
	public function registerAutoload()
	{
		$this->registerCoreAutoload();
		$this->registerPluginsAutoload();
	}

	/**
	 * @param Workspace $workspace
	 */
	public function setWorkspace(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * @api
	 * @return \Change\Workspace
	 */
	public function getWorkspace()
	{
		if (!$this->workspace)
		{
			$this->workspace = new \Change\Workspace();
		}
		return $this->workspace;
	}

	/**
	 * @param Configuration\Configuration $configuration
	 */
	public function setConfiguration(Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * Return the entire configuration or a specific entry if $entryName is not null
	 * @api
	 * @param string $entryName
	 * @return Configuration\Configuration|mixed|null
	 */
	public function getConfiguration($entryName = null)
	{
		if ($this->configuration === null)
		{
			$this->configuration = new Configuration\Configuration($this->getProjectConfigurationPaths());
		}
		if ($entryName)
		{
			return $this->configuration->getEntry($entryName);
		}
		return $this->configuration;
	}

	/**
	 * @api
	 * Call this to start application!
	 */
	public function start($bootStrapClass = null)
	{
		if (!$this->started())
		{
			// @codeCoverageIgnoreStart
			if (!defined('PROJECT_HOME'))
			{
				define('PROJECT_HOME', dirname(__DIR__));
			}
			// @codeCoverageIgnoreEnd
			$this->registerAutoload();
			if ($bootStrapClass && method_exists($bootStrapClass, 'main'))
			{
				call_user_func(array($bootStrapClass, 'main'), $this);
			}
			else
			{
				$bootStrapFilePath = $this->getWorkspace()->appPath('OldBootstrap.php');
				if (file_exists($bootStrapFilePath))
				{
					require_once $bootStrapFilePath;
					if (class_exists('\App\Bootstrap', false))
					{
						\App\Bootstrap::main($this);
					}
				}
			}
			$this->started = true;
		}
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function started()
	{
		return $this->started;
	}

	/**
	 * Clear cached files (config, ...)
	 * @api
	 */
	public function clearCache()
	{
		\Change\Stdlib\File::rmdir($this->getWorkspace()->cachePath());
	}

	/**
	 * Get all the project-level config files paths, in the correct order
	 * @api
	 * @return array string
	 */
	public function getProjectConfigurationPaths()
	{
		return [
			Configuration\Configuration::AUTOGEN => $this->getWorkspace()
					->appPath('Config', Configuration\Configuration::AUTOGEN),
			Configuration\Configuration::PROJECT => $this->getWorkspace()->appPath('Config', Configuration\Configuration::PROJECT)
		];
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	public function getLogging()
	{
		if ($this->logging === null)
		{
			$this->logging = new \Change\Logging\Logging();
			$this->logging->setConfiguration($this->getConfiguration());
			$this->logging->setWorkspace($this->getWorkspace());
		}
		return $this->logging;
	}



	/**
	 * @api
	 * @see project config
	 * @return boolean
	 */
	public function inDevelopmentMode()
	{
		return $this->getConfiguration()->inDevelopmentMode();
	}
}