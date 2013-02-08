<?php
namespace Change;

use Zend\Json\Json;

/**
 * @name \Change\Application
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
	 * @var \Change\Application\PackageManager
	 */
	protected $packageManager;

	/**
	 * @var \Change\Application
	 */
	protected static $sharedInstance;

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

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
	 * Registers the core autoload.
	 */
	public function registerCoreAutoload()
	{
		$namespaces = array('Change' => PROJECT_HOME . DIRECTORY_SEPARATOR . 'Change',
			'Zend' => PROJECT_HOME . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'zendframework' . DIRECTORY_SEPARATOR . 'zendframework' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'Zend');

		require_once $namespaces['Zend'] . DIRECTORY_SEPARATOR . 'Loader' . DIRECTORY_SEPARATOR . 'StandardAutoloader.php';
		$zendLoader = new \Zend\Loader\StandardAutoloader();
		foreach ($namespaces as $namespace => $path)
		{
			$zendLoader->registerNamespace($namespace, $path);
		}
		$zendLoader->register();
	}

	/**
	 * Register autoload for compiled code.
	 */
	public function registerCompilationAutoload()
	{
		// Register the compilation namespace
		$zendLoader = new \Zend\Loader\StandardAutoloader();
		$zendLoader->registerNamespace('Compilation', $this->getWorkspace()->compilationPath());
		$zendLoader->register();
	}

	/**
	 * Register autoload for packages
	 */
	public function registerPackagesAutoload()
	{
		$zendLoader = new \Zend\Loader\StandardAutoloader();
		// Register additional packages autoload
		foreach ($this->getPackageManager()->getRegisteredAutoloads() as $namespace => $path)
		{
			$zendLoader->registerNamespace($namespace, $path);
		}
		$zendLoader->register();
	}

	/**
	 * Namespace-based autoloading
	 */
	public function registerNamespaceAutoload()
	{
		$this->registerCoreAutoload();
		$this->registerCompilationAutoload();
		$this->registerPackagesAutoload();
	}

	/**
	 *
	 * @return string
	 */
	public function getProfile()
	{
		$profile = 'default';
		if (file_exists(PROJECT_HOME . '/profile'))
		{
			$profile = trim(file_get_contents(PROJECT_HOME . '/profile'));
		}
		return $profile;
	}

	/**
	 * Set the application services DiC
	 *
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 *
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		if (!$this->applicationServices)
		{
			$this->applicationServices  = new \Change\Application\ApplicationServices($this);
		}
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		if (!$this->documentServices)
		{
			if (!class_exists('Compilation\Change\Documents\AbstractDocumentServices'))
			{
				throw new \RuntimeException('Documents are not compiled.');
			}
			$this->setDocumentServices(new \Change\Documents\DocumentServices($this->getApplicationServices()));
		}
		return $this->documentServices;
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
	 * @param Workspace $workspace
	 */
	public function setWorkspace(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
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
	 * @param \Change\Application\PackageManager $packageManager
	 */
	public function setPackageManager($packageManager)
	{
		$this->packageManager = $packageManager;
	}

	/**
	 * @return \Change\Application\PackageManager
	 */
	public function getPackageManager()
	{
		if ($this->packageManager === null)
		{
			$this->packageManager = new \Change\Application\PackageManager($this->getWorkspace());
		}
		return $this->packageManager;
	}

	/**
	 * @param Configuration\Configuration $configuration
	 */
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
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
			$this->registerNamespaceAutoload();
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
			$this->started = true;
		}
	}

	/**
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
		$result = array();
		$globalConfigFile = $this->getWorkspace()->appPath('Config', 'project.json');
		if (file_exists($globalConfigFile))
		{
			$result[] = $globalConfigFile;
		}
		$profileConfigFile = $this->getWorkspace()->appPath('Config', 'project.' . $this->getProfile() . '.json');
		if (file_exists($profileConfigFile))
		{
			$result[] = $profileConfigFile;
		}
		return $result;
	}

	/**
	 * @see project config
	 * @return boolean
	 */
	public function inDevelopmentMode()
	{
		return $this->getConfiguration()->getEntry('general/development-mode', false);
	}
}