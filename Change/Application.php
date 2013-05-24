<?php
namespace Change;

use Zend\EventManager\EventManager;

/**
 * @name \Change\Application
 * @api
 */
class Application
{
	const CHANGE_VERSION = "4.0";

	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;

	/**
	 * @var \Change\Events\SharedEventManager
	 */
	protected $sharedEventManager;

	/**
	 * @var \Change\Application
	 */
	protected static $sharedInstance;

	/**
	 * @var bool
	 */
	protected $started = false;

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return self::CHANGE_VERSION;
	}

	/**
	 * Injection-based autoload if you want injection to work, this should be the
	 * last autoload coming from RBS Change you should register
	 * (it gets prepended to the autoload stack).
	 */
	public function registerInjectionAutoload()
	{
		$basePath = $this->getWorkspace()->compilationPath('Injection');
		spl_autoload_register(function ($className) use($basePath)
		{
			$phpFileName = str_replace('\\', '_', $className) . '.php';
			$phpFilePath = $basePath . DIRECTORY_SEPARATOR . '_' . $phpFileName;
			if (file_exists($phpFilePath))
			{
				require_once $phpFilePath;
			}
		}, true, true);
	}

	/**
	 * @return \Composer\Autoload\ClassLoader|null
	 */
	public function registerCoreAutoload()
	{
		return require_once PROJECT_HOME . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'autoload.php';
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
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @api
	 * @return \Change\Configuration\Configuration
	 */
	public function getConfiguration()
	{
		if ($this->configuration === null)
		{
			$this->configuration = new \Change\Configuration\Configuration($this->getProjectConfigurationPaths());
		}
		return $this->configuration;
	}


	/**
	 * @param \Change\Events\SharedEventManager $eventManager
	 */
	public function setSharedEventManager(\Change\Events\SharedEventManager $eventManager)
	{
		$this->sharedEventManager = $eventManager;
	}

	/**
	 * @api
	 * @return \Change\Events\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		if ($this->sharedEventManager === null)
		{
			$this->sharedEventManager = new \Change\Events\SharedEventManager();
			$this->sharedEventManager->attachConfiguredListeners($this->getConfiguration());
		}
		return $this->sharedEventManager;
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
			
			if ($this->inDevelopmentMode())
			{
				$injection = new \Change\Injection\Injection($this->getConfiguration(), $this->getWorkspace());
				$injection->update();
			}
			$this->registerInjectionAutoload();

			$this->dispatchStart();

			$this->started = true;
		}
	}

	protected function dispatchStart()
	{
		$eventManager = new EventManager('application');
		$eventManager->setSharedManager($this->getSharedEventManager());
		$eventManager->trigger('start', $this);
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
	 *
	 * @api
	 * @return array string
	 */
	public function getProjectConfigurationPaths()
	{
		$workspace = $this->getWorkspace();
		$result = array();
		$globalConfigFile = $workspace->appPath('Config', 'project.json');
		if (file_exists($globalConfigFile))
		{
			$result[] = $globalConfigFile;
		}

		//@TODO Fix instance config file name
		$instanceConfigFile = $workspace->appPath('Config', 'project.default.json');
		if (file_exists($instanceConfigFile))
		{
			$result[] = $instanceConfigFile;
		}
		return $result;
	}

	/**
	 * @api
	 * @see project config
	 * @return boolean
	 */
	public function inDevelopmentMode()
	{
		return $this->getConfiguration()->getEntry('Change/Application/development-mode', false);
	}
}