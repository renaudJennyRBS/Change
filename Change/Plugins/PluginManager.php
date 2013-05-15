<?php
namespace Change\Plugins;

use Change\Db\DbProvider;
use Change\Db\Query\ResultsConverter;
use Change\Db\ScalarType;
use Change\Workspace;
use Zend\Stdlib\Glob;

/**
 * @api
 * @name \Change\Plugins\PluginManager
 */
class PluginManager
{
	/**
	 * @var Workspace
	 */
	protected $workspace;

	/**
	 * @var DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var Plugin[]
	 */
	protected $plugins;

	/**
	 * @param Workspace $workspace
	 */
	public function setWorkspace(Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * @return Workspace
	 */
	protected function getWorkspace()
	{
		return $this->workspace;
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
	 * @return string
	 */
	protected function getCompiledPluginsPath()
	{
		return $this->getWorkspace()->compilationPath('Change', 'Plugins.ser');
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
		$projectModulesPattern = $this->getWorkspace()->projectModulesPath('*', 'plugin.json');
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
		$projectThemesPattern = $this->getWorkspace()->projectThemesPath('*', 'plugin.json');
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
		$datas = array();
		foreach ($plugins as $plugin)
		{
			$datas[] = $plugin->toArray();
		}

		\Change\Stdlib\File::write($this->getCompiledPluginsPath(), serialize($datas));
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
		return array_filter($allPlugins, function(Plugin $p) use ($registered) {
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


		$vendor = strtolower($config['vendor']);
		$normalizedVendor = ucfirst($vendor);
		$folderName = $parts[$partsCount - 3];
		if ($normalizedVendor === 'Project')
		{
			if ($folderName !== $type)
			{
				var_export($normalizedVendor . ' A-A ' . $folderName  . ' - ' . $type);
				return null;
			}
		}
		elseif ($normalizedVendor !== $folderName)
		{
			var_export($normalizedVendor . ' B-B ' . $folderName  . ' - ' . $type);
			return null;
		}

		$shortName = strtolower($config['name']);
		$normalizedShortName = ucfirst($shortName);
		$folderName = $parts[$partsCount - 2];
		if ($normalizedShortName !== $folderName)
		{
			var_export($normalizedShortName . ' - ' . $folderName  . ' - ' . $type);
			return null;
		}

		$basePath = dirname($filePath);
		if (is_readable($basePath . DIRECTORY_SEPARATOR . 'Plugin.php'))
		{
			$className =  ($type === Plugin::TYPE_THEME ? '\\Theme\\' : '\\' )  . $normalizedVendor . '\\' . $normalizedShortName . '\\Plugin';
			require_once $basePath . DIRECTORY_SEPARATOR . 'Plugin.php';
			if (class_exists($className, false))
			{
				$plugin = new $className($basePath, $type, $vendor, $shortName);
				if ($plugin instanceof Plugin)
				{
					return $plugin;
				}
			}
		}
		else
		{
			return new Plugin($basePath, $type, $vendor, $shortName);
		}
		return null;
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
				$pluginsDatas = unserialize(file_get_contents($compiledPluginsPath));
				foreach($pluginsDatas as $pluginData)
				{
					$basePath = $pluginData['basePath'];
					$type = $pluginData['type'];
					$vendor = $pluginData['vendor'];
					$shortName = $pluginData['shortName'];
					$className = $pluginData['className'];

					/* @var $plugin Plugin */
					$plugin = new $className($basePath, $type, $vendor, $shortName);
					if (array_key_exists('registrationDate', $pluginData))
					{
						$plugin->setRegistrationDate($pluginData['registrationDate']);
					}
					if (isset($pluginData['package']))
					{
						$plugin->setPackage($pluginData['package']);
					}
					if (isset($pluginData['activated']))
					{
						$plugin->setActivated($pluginData['activated']);
					}
					if (isset($pluginData['configured']))
					{
						$plugin->setConfigured($pluginData['configured']);
					}
					if (isset($pluginData['configuration']))
					{
						$plugin->setConfiguration($pluginData['configuration']);
					}
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
		$this->unRegister($plugin);
		$registrationDate = $plugin->getRegistrationDate();
		if (!($registrationDate instanceof \DateTime))
		{
			$registrationDate = new \DateTime();
			$plugin->setRegistrationDate($registrationDate);
		}
		$isb = $this->getDbProvider()->getNewStatementBuilder('PluginManager::register');
		$fb = $isb->getFragmentBuilder();
		$isb->insert($fb->getPluginTable(), 'type', 'vendor', 'name', 'registration_date');
		$isb->addValues($fb->parameter('type', $isb), $fb->parameter('vendor', $isb), $fb->parameter('name', $isb), $fb->dateTimeParameter('registrationDate', $isb));
		$iq = $isb->insertQuery();
		$iq->bindParameter('type', $plugin->getType());
		$iq->bindParameter('vendor', $plugin->getVendor());
		$iq->bindParameter('name', $plugin->getShortName());
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
	public function unRegister(Plugin $plugin)
	{
		$dsb = $this->getDbProvider()->getNewStatementBuilder('PluginManager::unRegister');
		$fb = $dsb->getFragmentBuilder();
		$dsb->delete($fb->getPluginTable())
			->where(
				$fb->logicAnd($fb->eq($fb->getDocumentColumn('type'), $fb->parameter('type', $dsb)),
				$fb->eq($fb->getDocumentColumn('vendor'), $fb->parameter('vendor', $dsb)),
				$fb->eq($fb->getDocumentColumn('name'), $fb->parameter('name', $dsb)))
			);

		$dq = $dsb->deleteQuery();
		$dq->bindParameter('type', $plugin->getType());
		$dq->bindParameter('vendor', $plugin->getVendor());
		$dq->bindParameter('name', $plugin->getShortName());
		$dq->execute();

		if ($this->plugins !== null)
		{
			$this->plugins = array_filter($this->plugins, function (Plugin $p) use($plugin) {
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
		$resultsConverter = new ResultsConverter($this->getDbProvider(),
			array('registration_date' => ScalarType::DATETIME,
				'configured' => ScalarType::BOOLEAN,
				'activated' => ScalarType::BOOLEAN,
				'config_datas' => ScalarType::LOB));

		$registered = $sqb->from($fb->getPluginTable())->query()->getResults(array($resultsConverter, 'convertRows'));
		$plugins = array_filter($plugins, function (Plugin $plugin) use ($registered)
		{
			foreach ($registered as $infos)
			{
				if ($infos['type'] === $plugin->getType() && $infos['vendor'] === $plugin->getVendor()
					&& $infos['name'] === $plugin->getShortName())
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
		$sqb = $this->getDbProvider()->getNewQueryBuilder();
		$fb = $sqb->getFragmentBuilder('PluginManager::load');
		$sqb->select('package', 'registration_date', 'configured', 'activated', 'config_datas')
			->where(
				$fb->logicAnd($fb->eq($fb->getDocumentColumn('type'), $fb->parameter('type', $sqb)),
					$fb->eq($fb->getDocumentColumn('vendor'), $fb->parameter('vendor', $sqb)),
					$fb->eq($fb->getDocumentColumn('name'), $fb->parameter('name', $sqb)))
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
			->assign('package', $fb->parameter('package', $usb))
			->assign('activated', $fb->typedParameter('activated', ScalarType::BOOLEAN, $usb))
			->assign('configured', $fb->typedParameter('configured', ScalarType::BOOLEAN, $usb))
			->assign('config_datas', $fb->typedParameter('configDatas', ScalarType::LOB, $usb))
			->where(
				$fb->logicAnd($fb->eq($fb->getDocumentColumn('type'), $fb->parameter('type', $usb)),
					$fb->eq($fb->getDocumentColumn('vendor'), $fb->parameter('vendor', $usb)),
					$fb->eq($fb->getDocumentColumn('name'), $fb->parameter('name', $usb)))
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
	 * @param string $vendor
	 * @param string $shortName
	 * @return Plugin|null
	 */
	public function getModule($vendor, $shortName)
	{
		$vendor = strtolower($vendor);
		$shortName = strtolower($shortName);
		$result = array_filter($this->getPlugins(), function(Plugin $plugin) use ($vendor, $shortName) {
			return $plugin->getType() === Plugin::TYPE_MODULE && $plugin->getVendor() === $vendor && $plugin->getShortName() === $shortName;
		});
		return array_pop($result);
	}

	/**
	 * @param string $vendor
	 * @return Plugin[]
	 */
	public function getModules($vendor = null)
	{
		$vendor = ($vendor) ? strtolower($vendor) : null;
		return array_filter($this->getPlugins(), function(Plugin $plugin) use ($vendor) {
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
		$vendor = strtolower($vendor);
		$shortName = strtolower($shortName);
		$result = array_filter($this->getPlugins(), function(Plugin $plugin) use ($vendor, $shortName) {
			return $plugin->getType() === Plugin::TYPE_THEME && $plugin->getVendor() === $vendor && $plugin->getShortName() === $shortName;
		});
		return array_pop($result);
	}

	/**
	 * @param string $vendor
	 * @return Plugin[]
	 */
	public function getThemes($vendor = null)
	{
		$vendor = ($vendor) ? strtolower($vendor) : null;
		return array_filter($this->getPlugins(), function(Plugin $plugin) use ($vendor) {
			return $plugin->getType() === Plugin::TYPE_THEME && ($vendor === null || $plugin->getVendor() === $vendor);
		});
	}
}