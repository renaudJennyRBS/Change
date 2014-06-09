<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @var \Zend\EventManager\SharedEventManager
	 */
	protected $sharedEventManager;



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
			$classLoader->setPsr4('Compilation\\', [PROJECT_HOME . DIRECTORY_SEPARATOR . 'Compilation']);
		}
		return $classLoader;
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
			$envMapping = null;
			$envMappingPath = $this->getWorkspace()->appPath('Config', 'env.json');
			if (is_readable($envMappingPath))
			{
					$data = \Change\Stdlib\File::read($envMappingPath);
					$envMapping = \Zend\Json\Json::decode($data, \Zend\Json\Json::TYPE_ARRAY);
			}
			$this->configuration = new Configuration\Configuration($this->getProjectConfigurationPaths(), null, $envMapping);
		}
		if ($entryName)
		{
			return $this->configuration->getEntry($entryName);
		}
		return $this->configuration;
	}

	/**
	 * @param \Zend\EventManager\SharedEventManager $sharedEventManager
	 * @return $this
	 */
	public function setSharedEventManager(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		$this->sharedEventManager = $sharedEventManager;
		$this->sharedEventManager->attach('*', '*', function(\Zend\EventManager\Event $event) {
			$event->setParam('application', $this);
			$event->setParam('services', new \Zend\Stdlib\Parameters());
			return true;
		}, 10000);

		$classNames = $this->getConfiguredListenerClassNames('Change/Events/ListenerAggregateClasses');

		foreach ($classNames as $className)
		{
			if (is_string($className) && class_exists($className))
			{
				$listenerAggregate = new $className();
				if ($listenerAggregate instanceof \Zend\EventManager\SharedListenerAggregateInterface)
				{
					$listenerAggregate->attachShared($this->sharedEventManager);
				}
			}
			else
			{
				$this->getLogging()->error($className . ' Shared Listener aggregate Class name not found.');
			}

		}
		return $this;
	}

	/**
	 * @api
	 * @return \Zend\EventManager\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager(new \Zend\EventManager\SharedEventManager());
		}
		return $this->sharedEventManager;
	}

	/**
	 * @api
	 * @param array|int|null|string|\Traversable $identifiers
	 * @param string|string[] $configPathOrClassNames
	 * @return \Change\Events\EventManager
	 */
	public function getNewEventManager($identifiers, $configPathOrClassNames = null)
	{
		$eventManager = new \Change\Events\EventManager($identifiers);
		$eventManager->setSharedManager($this->getSharedEventManager());

		if (is_string($configPathOrClassNames))
		{
			$classNames = $this->getConfiguredListenerClassNames($configPathOrClassNames);
		}
		elseif(is_array($configPathOrClassNames))
		{
			$classNames = $configPathOrClassNames;
		}
		else
		{
			$classNames = [];
		}

		foreach ($classNames as $className)
		{
			if (is_string($className) && class_exists($className))
			{
				$listenerAggregate = new $className();
				if ($listenerAggregate instanceof \Zend\EventManager\ListenerAggregateInterface)
				{
					$listenerAggregate->attach($eventManager);
				}
			}
			else
			{
				$this->getLogging()->error($className . ' Listener aggregate Class name not found.');
			}
		}
		return $eventManager;
	}

	/**
	 * @api
	 * @param $configurationEntryName
	 * @return array
	 */
	public function getConfiguredListenerClassNames($configurationEntryName)
	{
		if (is_string($configurationEntryName))
		{
			$configuration = $this->getConfiguration();
			$classNames = $configuration->getEntry($configurationEntryName);
			return is_array($classNames) ? $classNames : array();
		}
		return array();
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
		$configs = [
			Configuration\Configuration::AUTOGEN => $this->getWorkspace()
					->appPath('Config', 'project.autogen.json'),
			Configuration\Configuration::PROJECT => $this->getWorkspace()->appPath('Config', 'project.json'),
		];
		$changeInstanceConfigPath = getenv('CHANGE_INSTANCE_CONFIG_FILENAME');
		if ($changeInstanceConfigPath === false)
		{
			$changeInstanceConfigPath = 'project.instance.json';
		}
		$configs[Configuration\Configuration::INSTANCE] = $this->getWorkspace()->appPath('Config', $changeInstanceConfigPath);
		return $configs;
	}

	/**
	 * @param \Change\Logging\Logging $logging
	 * @return $this
	 */
	public function setLogging(\Change\Logging\Logging $logging = null)
	{
		$this->logging = $logging;
		return $this;
	}

	/**
	 * @api
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