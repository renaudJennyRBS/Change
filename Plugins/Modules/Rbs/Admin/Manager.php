<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin;

use Assetic\AssetManager;

/**
 * @name \Rbs\Admin\Manager
 */
class Manager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Documents\ModelManager
	 */
	protected $modelManager;

	/**
	 * @var \Change\Plugins\PluginManager
	 */
	protected $pluginManager;

	/**
	 * @var \Assetic\AssetManager
	 */
	protected $jsAssetManager;

	/**
	 * @var \Assetic\AssetManager
	 */
	protected $cssAssetManager;

	/**
	 * @var string
	 */
	protected $cachePath;

	/**
	 * @var \Twig_ExtensionInterface[]
	 */
	protected $extensions;

	public function __construct()
	{
		$this->jsAssetManager = new AssetManager();
		$this->cssAssetManager = new AssetManager();
	}

	/**
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Change\Plugins\PluginManager $pluginManager
	 * @return $this
	 */
	public function setPluginManager($pluginManager)
	{
		$this->pluginManager = $pluginManager;
		return $this;
	}

	/**
	 * @return \Change\Plugins\PluginManager
	 */
	protected function getPluginManager()
	{
		return $this->pluginManager;
	}

	/**
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return $this
	 */
	public function setModelManager($modelManager)
	{
		$this->modelManager = $modelManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\ModelManager
	 */
	protected function getModelManager()
	{
		return $this->modelManager;
	}

	/**
	 * @api
	 * @param \Twig_ExtensionInterface $extension
	 * @return $this
	 */
	public function addExtension(\Twig_ExtensionInterface $extension)
	{
		$this->getExtensions();
		$this->extensions[$extension->getName()] = $extension;
		return $this;
	}

	/**
	 * @return \Twig_ExtensionInterface[]
	 */
	public function getExtensions()
	{
		if ($this->extensions === null)
		{
			$extension = new \Rbs\Admin\Presentation\Twig\Extension($this->getI18nManager(), $this->getModelManager());
			$this->extensions = array($extension->getName() => $extension);
		}
		return $this->extensions;
	}

	/**
	 * @return \Assetic\AssetManager
	 */
	public function getCssAssetManager()
	{
		return $this->cssAssetManager;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return 'Rbs_Admin';
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Admin/Events/Manager');
	}

	/**
	 * @return array
	 */
	public function getResources()
	{
		$devMode = $this->getApplication()->inDevelopmentMode();

		$this->registerAdminResources($devMode);

		$pm = $this->getPluginManager();
		foreach ($pm->getInstalledPlugins() as $plugin)
		{
			if ($plugin->isModule())
			{
				$this->registerStandardPluginAssets($plugin, $devMode);
			}
		}

		$params = new \ArrayObject(array('header' => array(), 'body' => array()));
		$event = new Event(Event::EVENT_RESOURCES, $this, $params);
		$this->getEventManager()->trigger($event);
		return $params->getArrayCopy();
	}

	/**
	 * @param boolean $devMode
	 */
	protected function registerAdminResources($devMode)
	{
		$i18nManager = $this->getI18nManager();
		$lcid = strtolower(str_replace('_', '-', $i18nManager->getLCID()));

		$pluginPath = __DIR__ . '/Assets';
		$jsAssets = new \Assetic\Asset\AssetCollection();
		$path = $pluginPath . '/lib/moment/i18n/' . $lcid . '.js';
		if (file_exists($path))
		{
			$jsAssets->add(new \Assetic\Asset\FileAsset($path));
		}
		$path = $pluginPath . '/lib/angular/i18n/angular-locale_' . $lcid . '.js';
		if (file_exists($path))
		{
			$jsAssets->add(new \Assetic\Asset\FileAsset($path));
		}

		if (count($jsAssets->all()))
		{
			$this->getJsAssetManager()->set('i18n_' . $i18nManager->getLCID(), $jsAssets);
		}

		$jsAssets = new \Assetic\Asset\AssetCollection();
		$jsAssets->add(new \Assetic\Asset\FileAsset($pluginPath . '/js/rbschange.js'));

		$jsAssets->add(new \Assetic\Asset\GlobAsset($pluginPath . '/js/*/*.js'));
		$jsAssets->add(new \Assetic\Asset\FileAsset($pluginPath . '/menu/menu.js'));
		$jsAssets->add(new \Assetic\Asset\FileAsset($pluginPath . '/clipboard/controllers.js'));
		$jsAssets->add(new \Assetic\Asset\FileAsset($pluginPath . '/dashboard/controllers.js'));

		$jsAssets->add(new \Assetic\Asset\FileAsset($pluginPath . '/js/routes.js'));
		if (!$devMode)
		{
			$jsAssets->ensureFilter(new \Assetic\Filter\JSMinFilter());
		}

		$this->getJsAssetManager()->set('Rbs_Admin', $jsAssets);

		$cssAsset = new \Assetic\Asset\AssetCollection();
		$cssAsset->add(new \Assetic\Asset\GlobAsset($pluginPath . '/css/*.css'));
		$cssAsset->add(new \Assetic\Asset\FileAsset($pluginPath . '/menu/menu.css'));
		$cssAsset->add(new \Assetic\Asset\FileAsset($pluginPath . '/dashboard/dashboard.css'));

		$this->getCssAssetManager()->set('Rbs_Admin', $cssAsset);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param $devMode
	 */
	protected function registerStandardPluginAssets(\Change\Plugins\Plugin $plugin = null, $devMode)
	{
		$adminAssetsPath = $plugin->getAssetsPath() . '/Admin';
		if (is_dir($adminAssetsPath))
		{
			$globs = [$adminAssetsPath . '/*.js', $adminAssetsPath . '/Documents/*/*.js'];
			$jsAssets = new \Assetic\Asset\GlobAsset($globs);
			if (!$devMode)
			{
				$jsAssets->ensureFilter(new \Assetic\Filter\JSMinFilter());
			}
			$this->getJsAssetManager()->set($plugin->getName(), $jsAssets);

			$globs = [$adminAssetsPath . '/*.css'];
			$cssAsset = new \Assetic\Asset\GlobAsset($globs);
			$this->getCssAssetManager()->set($plugin->getName(), $cssAsset);
		}
	}

	/**
	 * @return array
	 */
	public function getMainMenu()
	{
		$mainMenu = ['sections' => [], 'entries' => []];
		$pm = $this->getPluginManager();
		$sections = [];
		$entries = [];
		foreach ($pm->getInstalledPlugins() as $plugin)
		{
			$mainMenuPath = $plugin->getAssetsPath() . '/Admin/main-menu.json';
			if (is_readable($mainMenuPath))
			{
				$menuJson = \Zend\Json\Json::decode(file_get_contents($mainMenuPath), \Zend\Json\Json::TYPE_ARRAY);
				if (is_array($menuJson))
				{
					$sections = array_merge($sections, $this->parseSections($menuJson));
					$entries = array_merge($entries, $this->parseEntries($menuJson));
				}
			}
		}
		uasort($sections, function($a, $b){
			$ida = isset($a['index']) ? $a['index'] : PHP_INT_MAX;
			$idb = isset($b['index']) ? $b['index'] : PHP_INT_MAX;
			return $ida >= $idb;
		});
		$defaultSection = null;
		if (count($sections))
		{
			reset($sections);
			$defaultSection = key($sections);
		}
		foreach ($entries as $entry)
		{
			$section = isset($entry['section']) && isset($sections[$entry['section']]) ? $entry['section'] : $defaultSection;
			if ($section)
			{
				$sections[$section]['entries'][] = $entry;
			}
		}

		$result = [];
		foreach ($sections as $section)
		{
			$entries = $section['entries'];
			usort($entries, function($a, $b){
				return strcmp(\Change\Stdlib\String::stripAccents($a['label']), \Change\Stdlib\String::stripAccents($b['label']));
			});
			$section['entries'] = $entries;
			$result[] = $section;
		}
		return $result;
	}

	/**
	 * @return string
	 */
	public function getResourceDirectoryPath()
	{
		$webBaseDirectory = $this->getApplication()->getConfiguration()->getEntry('Change/Install/webBaseDirectory');
		return $this->getApplication()->getWorkspace()->composeAbsolutePath($webBaseDirectory, 'Assets', 'Rbs', 'Admin');
	}

	/**
	 * @return string
	 */
	public function getResourceBaseUrl()
	{
		$webBaseURLPath = $this->getApplication()->getConfiguration()->getEntry('Change/Install/webBaseURLPath');
		return $webBaseURLPath . '/Assets/Rbs/Admin';
	}

	/**
	 * @param string $resourceDirectoryPath
	 */
	public function dumpResources($resourceDirectoryPath = null)
	{
		if ($resourceDirectoryPath === null)
		{
			$resourceDirectoryPath = $this->getResourceDirectoryPath();
		}

		$this->prepareCssAssets($resourceDirectoryPath);
		$this->prepareScriptAssets($resourceDirectoryPath);
		$this->prepareImageAssets($resourceDirectoryPath);
	}

	/**
	 * @return string
	 */
	protected function getCachePath()
	{
		if ($this->cachePath === null)
		{
			$this->cachePath = $this->getApplication()->getWorkspace()
				->cachePath('Admin', 'Templates', 'Compiled');
			\Change\Stdlib\File::mkdir($this->cachePath);
		}
		return $this->cachePath;
	}

	/**
	 * @param string $resourceDirectoryPath
	 * @param string $resourceBaseUrl
	 * @return array
	 */
	public function prepareScriptAssets($resourceDirectoryPath = null, $resourceBaseUrl = null)
	{
		$scripts = array();
		$am = $this->getJsAssetManager();
		foreach ($am->getNames() as $name)
		{
			$asset = $am->get($name);
			if ($asset instanceof \Assetic\Asset\AssetCollection)
			{
				if (count($asset->all()) === 0)
				{
					continue;
				}
			}
			$targetPath = '/js/' . $name . '.js';
			if ($resourceDirectoryPath !== null)
			{
				$fileTargetPath = $resourceDirectoryPath . str_replace('/', DIRECTORY_SEPARATOR, $targetPath);
				\Change\Stdlib\File::write($fileTargetPath, $asset->dump());
			}

			if ($resourceBaseUrl !== null)
			{
				$scripts[] = $resourceBaseUrl . $targetPath;
			}
		}
		return $scripts;
	}

	/**
	 * @param string $resourceDirectoryPath
	 * @param string $resourceBaseUrl
	 * @return array
	 */
	public function prepareCssAssets($resourceDirectoryPath = null, $resourceBaseUrl = null)
	{
		$scripts = array();
		$am = $this->getCssAssetManager();
		foreach ($am->getNames() as $name)
		{
			$asset = $am->get($name);
			if ($asset instanceof \Assetic\Asset\AssetCollection)
			{
				if (count($asset->all()) === 0)
				{
					continue;
				}
			}
			$targetPath = '/css/' . $name . '.css';
			if ($resourceDirectoryPath !== null)
			{
				$fileTargetPath = $resourceDirectoryPath . str_replace('/', DIRECTORY_SEPARATOR, $targetPath);
				\Change\Stdlib\File::write($fileTargetPath, $asset->dump());
			}

			if ($resourceBaseUrl !== null)
			{
				$scripts[] = $resourceBaseUrl . $targetPath;
			}
		}
		return $scripts;
	}

	/**
	 * @param string $resourceDirectoryPath
	 */
	public function prepareImageAssets($resourceDirectoryPath)
	{
		if (!$resourceDirectoryPath)
		{
			return;
		}
		$pm = $this->getPluginManager();
		$plugin = $pm->getModule('Rbs', 'Admin');
		$srcPath = $plugin->getAssetsPath() . '/img';
		$targetPath = $resourceDirectoryPath . '/img';
		\Change\Stdlib\File::mkdir($targetPath);

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcPath, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $fileInfo)
		{
			/* @var $fileInfo \SplFileInfo */
			$targetPathName = str_replace($srcPath, $targetPath, $fileInfo->getPathname());
			if ($fileInfo->isFile())
			{
				copy($fileInfo->getPathname(), $targetPathName);
			}
			elseif ($fileInfo->isDir())
			{
				\Change\Stdlib\File::mkdir($targetPathName);
			}
		}
	}

	/**
	 * @param string $pathName
	 * @param array $attributes
	 * @return string
	 */
	public function renderTemplateFile($pathName, array $attributes)
	{
		$loader = new \Twig_Loader_Filesystem(dirname($pathName));
		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		$twig->addExtension(new \Change\Presentation\Templates\Twig\Extension($this->getI18nManager()));
		foreach ($this->getExtensions() as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render(basename($pathName), $attributes);
	}

	/**
	 * @param string $moduleName
	 * @param string $pathName
	 * @param array $attributes
	 * @return string
	 */
	public function renderModuleTemplateFile($moduleName, $pathName, array $attributes)
	{
		$overridePath = $this->getApplication()->getWorkspace()->appPath('AdminOverrides');
		$loader = new \Rbs\Admin\Presentation\Twig\Loader($overridePath, $this->getPluginManager());
		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		$twig->addExtension(new \Change\Presentation\Templates\Twig\Extension($this->getI18nManager()));
		foreach ($this->getExtensions() as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render('@'.$moduleName.'/'.$pathName, $attributes);
	}

	/**
	 * @return array
	 */
	public function getRoutes()
	{
		$routes = [];
		foreach ($this->getPluginManager()->getModules() as $module)
		{
			if ($module->isAvailable()) {
				$filePath = $this->getApplication()->getWorkspace()->composePath($module->getAssetsPath(), 'Admin', 'routes.json');
				if (is_readable($filePath)) {
					$moduleRoutes = json_decode(file_get_contents($filePath), true);
					if (is_array($moduleRoutes))
					{
						foreach ($moduleRoutes as $path => $route)
						{
							if (is_array($route) && isset($route['rule'])) {
								unset($route['auto']);
								$routes[$path] = $route;
							}
						}
					}
				}
			}
		}
		return $routes;
	}

	/**
	 * @return \Assetic\AssetManager
	 */
	public function getJsAssetManager()
	{
		return $this->jsAssetManager;
	}

	/**
	 * @param array $menuJson
	 * @return array
	 */
	protected function parseSections($menuJson)
	{
		$result = [];
		if (isset($menuJson['sections']) && is_array($menuJson['sections']))
		{
			$i18nManager = $this->getI18nManager();
			foreach ($menuJson['sections'] as $item)
			{
				if (isset($item['code']))
				{
					if (isset($item['label']) && is_string($item['label']))
					{
						$item['label'] = $i18nManager->trans($item['label'], ['ucf']);
					}
					$result[$item['code']] = $item;
				}
			}
		}
		return $result;
	}

	private function parseEntries($menuJson)
	{
		$result = [];
		if (isset($menuJson['entries']) && is_array($menuJson['entries']))
		{
			$i18nManager = $this->getI18nManager();
			foreach ($menuJson['entries'] as $item)
			{
				if (isset($item['url']))
				{
					if (isset($item['label']) && is_string($item['label']))
					{
						$item['label'] = $i18nManager->trans($item['label'], ['ucf']);
					}
					if (isset($item['keywords']) && is_string($item['keywords']))
					{
						$item['keywords'] = $i18nManager->trans($item['keywords'], ['ucf']);
					}
					$result[] = $item;
				}
			}
		}
		return $result;
	}
}