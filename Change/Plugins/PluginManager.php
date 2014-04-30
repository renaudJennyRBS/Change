<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Plugins;

use Change\Db\DbProvider;
use Change\Db\Query\ResultsConverter;
use Change\Db\ScalarType;
use Change\Stdlib\File;
use Zend\Json\Json;
use Zend\Stdlib\Glob;

/**
 * @api
 * @name \Change\Plugins\PluginManager
 */
class PluginManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Plugin';

	const EVENT_SETUP_INITIALIZE = 'setupInitialize';
	const EVENT_SETUP_APPLICATION = 'setupApplication';
	const EVENT_SETUP_DB_SCHEMA = 'setupDbSchema';
	const EVENT_SETUP_SERVICES = 'setupServices';
	const EVENT_SETUP_FINALIZE = 'setupFinalize';
	const EVENT_SETUP_SUCCESS = 'setupSuccess';

	const EVENT_TYPE_PACKAGE = 'package';
	const EVENT_TYPE_MODULE = 'module';
	const EVENT_TYPE_THEME = 'theme';

	/**
	 * @var DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var Plugin[]
	 */
	protected $plugins;

	/**
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->getApplication()->getWorkspace();
	}

	/**
	 * @param DbProvider $dbProvider
	 */
	public function setDbProvider(DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @return DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager(\Change\Transaction\TransactionManager $transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	public function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @return string
	 */
	protected function getCompiledPluginsPath()
	{
		return $this->getWorkspace()->compilationPath('Change', 'Plugins.ser');
	}

	/**
	 * @api
	 * $step in PluginManager::EVENT_SETUP_*
	 * $type in PluginManager::EVENT_TYPE_*
	 * @param string $step
	 * @param string $type
	 * @param string $vendor
	 * @param string $name
	 * @return string
	 */
	public static function composeEventName($step, $type, $vendor, $name)
	{
		return $step . '_' . $type . '_' . $vendor . '_' . $name;
	}

	/**
	 * @return Plugin[]
	 */
	public function scanPlugins()
	{
		$plugins = array();

		// Plugin Modules.
		$pluginsModulesPattern = $this->getWorkspace()->pluginsModulesPath('*', '*', 'plugin.json');
		foreach (Glob::glob($pluginsModulesPattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT) as $filePath)
		{
			$plugin = $this->getNewPlugin($filePath);
			if ($plugin)
			{
				$plugins[] = $plugin;
			}
		}

		// Project modules.
		$projectModulesPattern = $this->getWorkspace()->projectModulesPath('Project', '*', 'plugin.json');
		foreach (Glob::glob($projectModulesPattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT) as $filePath)
		{
			$plugin = $this->getNewPlugin($filePath);
			if ($plugin)
			{
				$plugins[] = $plugin;
			}
		}

		// Plugin themes.
		$projectThemesPattern = $this->getWorkspace()->pluginsThemesPath('*', '*', 'plugin.json');
		foreach (Glob::glob($projectThemesPattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT) as $filePath)
		{
			$plugin = $this->getNewPlugin($filePath, Plugin::TYPE_THEME);
			if ($plugin)
			{
				$plugins[] = $plugin;
			}
		}

		// Project themes.
		$projectThemesPattern = $this->getWorkspace()->projectThemesPath('Project', '*', 'plugin.json');
		foreach (Glob::glob($projectThemesPattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT) as $filePath)
		{
			$plugin = $this->getNewPlugin($filePath, Plugin::TYPE_THEME);
			if ($plugin)
			{
				$plugins[] = $plugin;
			}
		}
		return $plugins;
	}

	/**
	 * @param boolean $checkRegistered
	 * @return Plugin[]
	 */
	public function compile($checkRegistered = true)
	{
		$plugins = $this->scanPlugins();

		if ($checkRegistered)
		{
			$plugins = $this->loadRegistration($plugins);
		}
		else
		{
			$now = new \DateTime();
			foreach ($plugins as $plugin)
			{
				$plugin->setActivated(true);
				$plugin->setConfigured(true);
				$plugin->setRegistrationDate($now);
			}
		}

		$this->plugins = $plugins;
		$autoloader = new Autoloader();
		$autoloader->setWorkspace($this->getWorkspace());

		\Change\Stdlib\File::write($this->getCompiledPluginsPath(), serialize($plugins));
		$autoloader->reset();
		return $plugins;
	}

	/**
	 * @return Plugin[]
	 */
	public function getUnregisteredPlugins()
	{
		$allPlugins = $this->scanPlugins();
		$registered = $this->loadRegistration($allPlugins);
		return array_filter($allPlugins, function (Plugin $p) use ($registered)
		{
			foreach ($registered as $rp)
			{
				if ($p->eq($rp))
				{
					return false;
				}
			}
			return true;
		});
	}

	/**
	 * @param string $filePath
	 * @param string $type
	 * @return Plugin|null
	 */
	protected function getNewPlugin($filePath, $type = Plugin::TYPE_MODULE)
	{
		$config = json_decode(file_get_contents($filePath), true);
		if (json_last_error() !== JSON_ERROR_NONE || !isset($config['vendor']) || !isset($config['name']))
		{
			return null;
		}
		$parts = explode(DIRECTORY_SEPARATOR, $filePath);
		$partsCount = count($parts);

		$vendor = $this->normalizeVendorName($config['vendor']);
		$shortName = $this->normalizePluginName($config['name']);

		$folderName = $parts[$partsCount - 3];
		if ($vendor === 'Project')
		{
			$folderName = $parts[$partsCount - 4];
			if ($folderName !== ($type == Plugin::TYPE_MODULE ? 'Modules' : 'Themes'))
			{
				return null;
			}
		}
		elseif ($vendor !== $folderName)
		{
			return null;
		}

		$folderName = $parts[$partsCount - 2];
		if ($shortName !== $folderName)
		{
			return null;
		}

		$plugin = new Plugin($type, $vendor, $shortName);
		$plugin->setWorkspace($this->getWorkspace());

		if (isset($config['package']))
		{
			$plugin->setPackage($config['package']);
		}

		if (isset($config['defaultLCID']))
		{
			$plugin->setDefaultLCID($config['defaultLCID']);
		}

		return $plugin;
	}

	/**
	 * @return Plugin[]
	 */
	public function getPlugins()
	{
		if ($this->plugins === null)
		{
			$this->plugins = array();
			$compiledPluginsPath = $this->getCompiledPluginsPath();
			if (is_readable($compiledPluginsPath))
			{
				$plugins = unserialize(file_get_contents($compiledPluginsPath));
				foreach ($plugins as $plugin)
				{
					/** @var $plugin Plugin */
					$plugin->setWorkspace($this->getWorkspace());
					$this->plugins[] = $plugin;
				}
			}
		}
		return $this->plugins;
	}

	public function reset()
	{
		$this->plugins = null;
	}

	/**
	 * @param Plugin $plugin
	 */
	public function register(Plugin $plugin)
	{
		$this->deregister($plugin);
		$registrationDate = $plugin->getRegistrationDate();
		if (!($registrationDate instanceof \DateTime))
		{
			$registrationDate = new \DateTime();
			$plugin->setRegistrationDate($registrationDate);
		}
		$isb = $this->getDbProvider()->getNewStatementBuilder('PluginManager::register');
		$fb = $isb->getFragmentBuilder();
		$isb->insert($fb->getPluginTable(), 'type', 'vendor', 'name', 'package', 'registration_date');
		$isb->addValues($fb->parameter('type'), $fb->parameter('vendor'), $fb->parameter('name'), $fb->parameter('package'),
			$fb->dateTimeParameter('registrationDate'));
		$iq = $isb->insertQuery();
		$iq->bindParameter('type', $plugin->getType());
		$iq->bindParameter('vendor', $plugin->getVendor());
		$iq->bindParameter('name', $plugin->getShortName());
		$iq->bindParameter('package', $plugin->getPackage());
		$iq->bindParameter('registrationDate', $registrationDate);
		$iq->execute();

		if ($this->plugins !== null)
		{
			$this->plugins[] = $plugin;
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function deregister(Plugin $plugin)
	{
		$dsb = $this->getDbProvider()->getNewStatementBuilder('PluginManager::unRegister');
		$fb = $dsb->getFragmentBuilder();
		$dsb->delete($fb->getPluginTable())
			->where(
				$fb->logicAnd($fb->eq($fb->getDocumentColumn('type'), $fb->parameter('type')),
					$fb->eq($fb->getDocumentColumn('vendor'), $fb->parameter('vendor')),
					$fb->eq($fb->getDocumentColumn('name'), $fb->parameter('name')))
			);

		$dq = $dsb->deleteQuery();
		$dq->bindParameter('type', $plugin->getType());
		$dq->bindParameter('vendor', $plugin->getVendor());
		$dq->bindParameter('name', $plugin->getShortName());
		$dq->execute();

		if ($this->plugins !== null)
		{
			$this->plugins = array_filter($this->plugins, function (Plugin $p) use ($plugin)
			{
				return !$p->eq($plugin);
			});
		}
	}

	/**
	 * @param Plugin[] $plugins
	 * @return Plugin[]
	 */
	protected function loadRegistration($plugins)
	{
		$sqb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $sqb->getFragmentBuilder();
		$sqb->select('type', 'vendor', 'name', 'package', 'registration_date', 'configured', 'activated', 'config_datas');
		$q = $sqb->from($fb->getPluginTable())->query();

		$converter = $q->getRowsConverter()->addStrCol('type', 'vendor', 'name', 'package')
			->addDtCol('registration_date')->addBoolCol('configured', 'activated')->addLobCol('config_datas');

		$registered = $q->getResults($converter);
		$plugins = array_filter($plugins, function (Plugin $plugin) use ($registered)
		{
			foreach ($registered as $infos)
			{
				if ($infos['type'] === $plugin->getType() && $infos['vendor'] === $plugin->getVendor()
					&& $infos['name'] === $plugin->getShortName()
				)
				{
					$plugin->setPackage($infos['package']);
					$plugin->setActivated($infos['activated']);
					$plugin->setConfigured($infos['configured']);
					$plugin->setRegistrationDate($infos['registration_date']);

					$configDatas = array();
					if ($infos['config_datas'])
					{
						$configDatas = json_decode($infos['config_datas'], true);
						if (!is_array($configDatas))
						{
							$configDatas = array();
						}
					}
					$plugin->setConfiguration($configDatas);
					return true;
				}
			}
			return false;
		});
		return array_values($plugins);
	}

	/**
	 * @param Plugin $plugin
	 * @return Plugin|null
	 */
	public function load($plugin)
	{
		$plugin->setWorkspace($this->getWorkspace());
		$sqb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $sqb->getFragmentBuilder('PluginManager::load');
		$sqb->select('package', 'registration_date', 'configured', 'activated', 'config_datas')
			->where(
				$fb->logicAnd($fb->eq($fb->getDocumentColumn('type'), $fb->parameter('type')),
					$fb->eq($fb->getDocumentColumn('vendor'), $fb->parameter('vendor')),
					$fb->eq($fb->getDocumentColumn('name'), $fb->parameter('name')))
			);

		$sq = $sqb->from($fb->getPluginTable())->query();
		$sq->bindParameter('type', $plugin->getType());
		$sq->bindParameter('vendor', $plugin->getVendor());
		$sq->bindParameter('name', $plugin->getShortName());

		$resultsConverter = new ResultsConverter($this->getDbProvider(),
			array('registration_date' => ScalarType::DATETIME,
				'configured' => ScalarType::BOOLEAN,
				'activated' => ScalarType::BOOLEAN,
				'config_datas' => ScalarType::LOB));

		$infos = $sqb->from($fb->getPluginTable())->query()->getFirstResult(array($resultsConverter, 'convertRow'));
		if (is_array($infos))
		{
			$plugin->setPackage($infos['package']);
			$plugin->setActivated($infos['activated']);
			$plugin->setConfigured($infos['configured']);
			$plugin->setRegistrationDate($infos['registration_date']);
			$configDatas = array();
			if ($infos['config_datas'])
			{
				$configDatas = json_decode($infos['config_datas'], true);
				if (!is_array($configDatas))
				{
					$configDatas = array();
				}
			}
			$plugin->setConfiguration($configDatas);
			return $plugin;
		}
		return null;
	}

	/**
	 * @param Plugin $plugin
	 */
	public function update($plugin)
	{
		$usb = $this->getDbProvider()->getNewStatementBuilder('PluginManager::update');
		$fb = $usb->getFragmentBuilder();
		$usb->update($fb->getPluginTable())
			->assign('package', $fb->parameter('package'))
			->assign('activated', $fb->booleanParameter('activated'))
			->assign('configured', $fb->booleanParameter('configured'))
			->assign('config_datas', $fb->lobParameter('configDatas'))
			->where(
				$fb->logicAnd($fb->eq($fb->getDocumentColumn('type'), $fb->parameter('type')),
					$fb->eq($fb->getDocumentColumn('vendor'), $fb->parameter('vendor')),
					$fb->eq($fb->getDocumentColumn('name'), $fb->parameter('name')))
			);

		$uq = $usb->updateQuery();

		$uq->bindParameter('package', $plugin->getPackage());
		$uq->bindParameter('activated', $plugin->getActivated());
		$uq->bindParameter('configured', $plugin->getConfigured());

		$configuration = $plugin->getConfiguration();
		if (!is_array($configuration) || !count($configuration))
		{
			$uq->bindParameter('configDatas', null);
		}
		else
		{
			$uq->bindParameter('configDatas', json_encode($configuration));
		}

		$uq->bindParameter('type', $plugin->getType());
		$uq->bindParameter('vendor', $plugin->getVendor());
		$uq->bindParameter('name', $plugin->getShortName());
		$uq->execute();
	}

	/**
	 * @param string $type
	 * @param string $vendor
	 * @param string $shortName
	 * @return Plugin|null
	 */
	public function getPlugin($type, $vendor, $shortName)
	{
		$vendor = $this->normalizeVendorName($vendor);
		$shortName = $this->normalizePluginName($shortName);
		$result = array_filter($this->getPlugins(), function (Plugin $plugin) use ($type, $vendor, $shortName)
		{
			return $plugin->getType() === $type && $plugin->getVendor() === $vendor && $plugin->getShortName() === $shortName;
		});
		return array_pop($result);
	}

	/**
	 * @param string $vendor
	 * @param string $shortName
	 * @return Plugin|null
	 */
	public function getModule($vendor, $shortName)
	{
		$vendor = $this->normalizeVendorName($vendor);
		$shortName = $this->normalizePluginName($shortName);
		$result = array_filter($this->getPlugins(), function (Plugin $plugin) use ($vendor, $shortName)
		{
			return $plugin->getType() === Plugin::TYPE_MODULE && $plugin->getVendor() === $vendor
			&& $plugin->getShortName() === $shortName;
		});
		return array_pop($result);
	}

	/**
	 * @param string $vendor
	 * @return Plugin[]
	 */
	public function getModules($vendor = null)
	{
		$vendor = ($vendor) ? $this->normalizeVendorName($vendor) : null;
		return array_filter($this->getPlugins(), function (Plugin $plugin) use ($vendor)
		{
			return $plugin->getType() === Plugin::TYPE_MODULE && ($vendor === null || $plugin->getVendor() === $vendor);
		});
	}

	/**
	 * @param string $vendor
	 * @param string $shortName
	 * @return Plugin|null
	 */
	public function getTheme($vendor, $shortName)
	{
		$vendor = $this->normalizeVendorName($vendor);
		$shortName = $this->normalizePluginName($shortName);
		$result = array_filter($this->getPlugins(), function (Plugin $plugin) use ($vendor, $shortName)
		{
			return $plugin->getType() === Plugin::TYPE_THEME && $plugin->getVendor() === $vendor
			&& $plugin->getShortName() === $shortName;
		});
		return array_pop($result);
	}

	/**
	 * @param string $vendor
	 * @return Plugin[]
	 */
	public function getThemes($vendor = null)
	{
		$vendor = ($vendor) ? $this->normalizeVendorName($vendor) : null;
		return array_filter($this->getPlugins(), function (Plugin $plugin) use ($vendor)
		{
			return $plugin->getType() === Plugin::TYPE_THEME && ($vendor === null || $plugin->getVendor() === $vendor);
		});
	}

	/**
	 * @return Plugin[]
	 */
	public function getRegisteredPlugins()
	{
		return array_filter($this->getPlugins(), function (Plugin $plugin)
		{
			return $plugin->getConfigured() === false;
		});
	}

	/**
	 * @return Plugin[]
	 */
	public function getInstalledPlugins()
	{
		return array_filter($this->getPlugins(), function (Plugin $plugin)
		{
			return $plugin->getConfigured() === true;
		});
	}

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return array
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/Plugin');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		foreach ($this->getPlugins() as $plugin)
		{
			$listenerAggregate = new Register($plugin);
			$listenerAggregate->attach($eventManager);
		}
	}

	/**
	 * @param string $vendor
	 * @param string $packageName
	 * @param array $context
	 * @return \Change\Plugins\Plugin[]
	 */
	public function installPackage($vendor, $packageName, $context = array())
	{
		$vendor = $this->normalizeVendorName($vendor);
		return $this->doInstall(static::EVENT_TYPE_PACKAGE, $vendor, $packageName, $context);
	}

	/**
	 * @param string $type
	 * @param string $vendor
	 * @param string $name
	 * @param array $context
	 * @throws \InvalidArgumentException
	 * @return \Change\Plugins\Plugin[]
	 */
	public function installPlugin($type, $vendor, $name, $context = array())
	{
		if ($type == 'module')
		{
			$eventType = static::EVENT_TYPE_MODULE;
		}
		elseif ($type == 'theme')
		{
			$eventType = static::EVENT_TYPE_THEME;
		}
		else
		{
			throw new \InvalidArgumentException('Type must be either "module" or "theme"');
		}

		$vendor = $this->normalizeVendorName($vendor);
		$name = $this->normalizeVendorName($name);
		return $this->doInstall($eventType, $vendor, $name, $context);
	}

	/**
	 * @param Plugin $plugin
	 * @throws \InvalidArgumentException
	 */
	public function deinstall(Plugin $plugin)
	{
		if (isset($plugin->getConfiguration()['locked']) && $plugin->getConfiguration()['locked'])
		{
			throw new \InvalidArgumentException('Plugin is locked, unable to deinstall');
		}
		else
		{
			//TODO do a real deinstall!
			$plugin->setActivated(false);
			$plugin->setConfigured(false);
			$date = new \DateTime();
			$plugin->setConfigurationEntry('deinstallDate', $date->format('c'));
			$this->update($plugin);
		}
	}

	/**
	 * @param string $eventType
	 * @param string $vendor
	 * @param string $name
	 * @param array $context
	 * @throws \Exception
	 * @return Plugin[]
	 */
	protected function doInstall($eventType, $vendor, $name, $context)
	{
		if (!($this->getApplication()->getConfiguration() instanceof \Change\Configuration\EditableConfiguration))
		{
			$editableConfiguration = new \Change\Configuration\EditableConfiguration(array());
			$this->getApplication()->setConfiguration($editableConfiguration->import($this->getApplication()->getConfiguration()));
		}
		else
		{
			$editableConfiguration = $this->getApplication()->getConfiguration();
		}

		$installEventManager = $this->getEventManager();

		/* @var $plugins \Change\Plugins\Plugin[] */
		$plugins = array();
		$eventArgs = $installEventManager->prepareArgs(array('context' => $context, 'type' => $eventType, 'vendor' => $vendor, 'name' => $name));

		$event = new \Change\Events\Event(static::EVENT_SETUP_INITIALIZE, $this, $eventArgs);
		$results = $installEventManager->trigger($event);

		$date = new \DateTime();
		foreach ($results as $result)
		{
			if ($result instanceof Plugin)
			{
				$result->setWorkspace($this->getWorkspace());
				$result->setActivated(true);
				$result->setConfigurationEntry('installDate', $date->format('c'));
				$plugins[] = $result;
			}
		}

		$eventArgs['plugins'] = $plugins;

		$event->setName(static::EVENT_SETUP_APPLICATION);
		$installEventManager->trigger($event);

		if ($eventType !== static::EVENT_TYPE_THEME)
		{
			$compiler = new \Change\Documents\Generators\Compiler($this->getApplication(), $event->getApplicationServices());
			$compiler->generate();

			$generator = new \Change\Db\Schema\Generator($this->getWorkspace(), $event->getApplicationServices()->getDbProvider());
			$generator->generatePluginsSchema();

			foreach ($plugins as $plugin)
			{
				$event->getApplicationServices()->getI18nManager()->compilePluginI18nFiles($plugin);
			}
		}

		$event->setName(static::EVENT_SETUP_DB_SCHEMA);
		$installEventManager->trigger($event);

		$event->setName(static::EVENT_SETUP_SERVICES);
		$installEventManager->trigger($event);

		$event->setName(static::EVENT_SETUP_FINALIZE);
		$installEventManager->trigger($event);

		try
		{
			$this->getTransactionManager()->begin();

			foreach ($plugins as $plugin)
			{
				$date = new \DateTime();
				$plugin->setConfigured(true);
				$plugin->setConfigurationEntry('configuredDate', $date->format('c'));
				$this->update($plugin);
			}
			$editableConfiguration->save();

			$this->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			throw $this->getTransactionManager()->rollBack($e);
		}

		$event->setName(static::EVENT_SETUP_SUCCESS);
		$installEventManager->trigger($event);

		return $plugins;
	}

	/**
	 * @param $name
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function normalizeVendorName($name)
	{
		$lcName = strtolower($name);
		if (!preg_match('/^[a-z][a-z0-9]{1,24}$/', $lcName))
		{
			throw new \InvalidArgumentException('Vendor name "' . $lcName . '" should match ^[a-z][a-z0-9]{1,24}$', 999999);
		}
		return ucfirst($lcName);
	}

	/**
	 * @param $name
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function normalizePluginName($name)
	{
		$lcName = strtolower($name);
		if (!preg_match('/^[a-z][a-z0-9]{1,24}$/', $lcName))
		{
			throw new \InvalidArgumentException('Plugin name "' . $lcName . '" should match ^[a-z][a-z0-9]{1,24}$', 999999);
		}
		return ucfirst($lcName);
	}

	/**
	 * @param string $type
	 * @param string $vendor
	 * @param string $name
	 * @param string|null $package
	 * @return string
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function initializePlugin($type, $vendor, $name, $package = null)
	{
		if ($type != 'module' && $type != 'theme')
		{
			throw new \InvalidArgumentException('Type must be either "module" or "theme"');
		}
		$normalizedVendor = $this->normalizeVendorName($vendor);
		$normalizedName = $this->normalizePluginName($name);
		$path = null;
		if ($type === 'module')
		{
			if ($normalizedVendor === 'Project')
			{
				$path = $this->getWorkspace()->projectModulesPath('Project', $normalizedName, 'plugin.json');
			}
			else
			{
				$path = $this->getWorkspace()->pluginsModulesPath($normalizedVendor, $normalizedName, 'plugin.json');
			}
			$twigFileName = 'Assets/Install.module.php.twig';
		}
		else
		{
			if ($normalizedVendor === 'Project')
			{
				$path = $this->getWorkspace()->projectThemesPath('Project', $normalizedName, 'plugin.json');
			}
			else
			{
				$path = $this->getWorkspace()->pluginsThemesPath($normalizedVendor, $normalizedName, 'plugin.json');
			}
			$twigFileName = 'Assets/Install.theme.php.twig';
		}
		if (file_exists($path))
		{
			throw new \RuntimeException('Plugin already exists at path ' . $path, 999999);
		}
		$attributes = array('type' => $type, 'vendor' => $normalizedVendor, 'name' => $normalizedName);
		if ($package)
		{
			$attributes['package'] = $package;
		}
		File::write($path, Json::prettyPrint(Json::encode($attributes)));

		$loader = new \Twig_Loader_Filesystem(__DIR__);
		$twig = new \Twig_Environment($loader);

		File::write(dirname($path) . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'Install.php',
			$twig->render($twigFileName, $attributes));

		$plugin = $this->getNewPlugin($path, $type === 'module' ? Plugin::TYPE_MODULE : Plugin::TYPE_THEME);
		$this->register($plugin);

		$this->compile();
		return dirname($path);
	}
}