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
	 * @var Configuration\Configuration
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
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

	/**
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
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext()
	{
		if ($this->context === null)
		{
			$this->context = new \Zend\Stdlib\Parameters();
		}
		return $this->context;
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
	public function setConfiguration(Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @api
	 * @return Configuration\Configuration
	 */
	public function getConfiguration()
	{
		if ($this->configuration === null)
		{
			$this->configuration = new Configuration\Configuration($this->getProjectConfigurationPaths());
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
			$this->dispatchStart();
			$this->started = true;
		}
	}

	protected function dispatchStart()
	{
		$eventManager = new EventManager('Application');
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
	 * @api
	 * @return array string
	 */
	public function getProjectConfigurationPaths()
	{
		$workspace = $this->getWorkspace();
		$result = array();
		$result[Configuration\Configuration::PROJECT] = $workspace->appPath('Config', 'project.json');
		$result[Configuration\Configuration::INSTANCE] = $workspace->appPath('Config', 'project.default.json');
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