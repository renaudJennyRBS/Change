<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\Theme;

class DefaultTheme implements Theme
{
	/**
	 * @var DefaultPageTemplate
	 */
	protected $defaultPageTemplate;

	/**
	 * @var \Change\Presentation\Themes\ThemeManager
	 */
	protected $themeManager;

	/**
	 * @var string
	 */
	protected $vendor;

	/**
	 * @var string
	 */
	protected $shortName;

	/**
	 * @var string $templateBasePath
	 */
	protected $templateBasePath;

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Change\Plugins\PluginManager
	 */
	protected $pluginManager;

	/**
	 * @param \Change\Application $application
	 */
	function __construct($application)
	{
		$this->application = $application;
		list($this->vendor, $this->shortName) = explode('_', $this->getName());
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return ThemeManager::DEFAULT_THEME_NAME;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @param ThemeManager $themeManager
	 * @return $this
	 */
	public function setThemeManager(ThemeManager $themeManager)
	{
		$this->themeManager = $themeManager;
		return $this;
	}

	/**
	 * @return \Change\Presentation\Themes\ThemeManager
	 */
	protected function getThemeManager()
	{
		return $this->themeManager;
	}

	/**
	 * @return null
	 */
	public function getParentTheme()
	{
		return null;
	}

	/**
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->getApplication()->getWorkspace();
	}

	/**
	 * @param \Change\Plugins\PluginManager $pluginManager
	 * @return $this
	 */
	public function setPluginManager(\Change\Plugins\PluginManager $pluginManager)
	{
		$this->pluginManager = $pluginManager;
		return $this;
	}

	/**
	 * @return \Change\Plugins\PluginManager
	 */
	public function getPluginManager()
	{
		return $this->pluginManager;
	}

	/**
	 * @return string
	 */
	public function getTemplateBasePath()
	{
		if ($this->templateBasePath === null)
		{
			$this->templateBasePath = $this->getWorkspace()->appPath('Themes', $this->vendor, $this->shortName);

			if ($this->getApplication()->inDevelopmentMode())
			{
				$pluginManager = $this->getPluginManager();
				$plugins = $pluginManager->getModules();
				foreach ($plugins as $plugin)
				{
					if ($plugin->isAvailable() && $plugin->getTwigAssetsPath())
					{
						$this->getThemeManager()->installPluginTemplates($plugin);
					}
				}
			}
		}
		return $this->templateBasePath;
	}

	/**
	 * @return string
	 */
	public function getAssetBasePath()
	{
		return $this->getWorkspace()->pluginsThemesPath($this->vendor, $this->shortName, 'Assets');
	}

	/**
	 * @param string $moduleName
	 * @param string $pathName
	 * @param string $content
	 * @return void
	 */
	public function installTemplateContent($moduleName, $pathName, $content)
	{
		$path =  $this->getWorkspace()->composePath($this->getTemplateBasePath(), $moduleName, $pathName);
		\Change\Stdlib\File::mkdir(dirname($path));
		file_put_contents($path, $content);
	}

	/**
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string
	 */
	public function getTemplateRelativePath($moduleName, $fileName)
	{
		return $this->getWorkspace()->composePath($moduleName, $fileName);
	}

	/**
	 * @param string $name
	 * @return \Change\Presentation\Interfaces\Template
	 */
	public function getPageTemplate($name)
	{
		if (is_numeric($name))
		{
			$name = 'default';
		}
		return new DefaultPageTemplate($this, $name);
	}

	/**
	 * @param string $resourcePath
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	public function getResource($resourcePath)
	{
		$path = $this->getResourceFilePath($resourcePath);
		return new FileResource($path);
	}

	/**
	 * @param string $assetPath
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	public function getAssetResource($assetPath)
	{
		$path = $this->getWorkspace()->composePath($this->getAssetBasePath(), $assetPath);
		return new FileResource($path);
	}

	/**
	 * @param string $resourcePath
	 * @return string
	 */
	public function getResourceFilePath($resourcePath)
	{
		if (preg_match('/^([A-Z][A-Aa-z0-9]+)_([A-Z][A-Aa-z0-9]+)\/(.+)$/', $resourcePath, $matches))
		{
			$vendor = $matches[1];
			$moduleShortName = $matches[2];
			$resourceModulePath = $matches[3];
			$pm = $this->getPluginManager();
			$module = $pm->getModule($vendor, $moduleShortName);
			if ($module && $module->isAvailable())
			{
				$path =  $this->getWorkspace()->composePath($module->getThemeAssetsPath(), $resourceModulePath);
				if (file_exists($path))
				{
					return $path;
				}
			}
		}
		return $this->getWorkspace()->composePath($this->getAssetBasePath(), $resourcePath);
	}

	/**
	 * @param array $baseConfiguration
	 * @return array
	 * @throws \RuntimeException
	 */
	public function getAssetConfiguration(array $baseConfiguration = null)
	{
		//first get themes configuration
		$configuration = is_array($baseConfiguration) ? $baseConfiguration : [];
		$resource = $this->getResourceFilePath('assets.json');
		if (file_exists($resource))
		{
			$configuration = array_merge($configuration, json_decode(\Change\Stdlib\File::read($resource), true));
		}
		else
		{
			throw new \RuntimeException('invalid resource assets.json configuration file of default theme', 999999);
		}

		//Now find all modules configuration file
		$pluginManager = $this->getPluginManager();
		$plugins = $pluginManager->getInstalledPlugins();
		foreach ($plugins as $plugin)
		{
			if ($plugin->isModule() && $plugin->isAvailable())
			{
				$configurationPath = $this->getWorkspace()->composePath($plugin->getThemeAssetsPath(), 'assets.json');
				if (file_exists($configurationPath))
				{
					$blockConfigurations = [];
					foreach (json_decode(\Change\Stdlib\File::read($configurationPath), true) as $blockName => $blockConfiguration)
					{
						$blockConfigurations[$plugin->getName() . '_' . $blockName] = $blockConfiguration;
					}
					$configuration = array_merge($configuration, $blockConfigurations);
				}
			}
		}
		return $configuration;
	}

	/**
	 * @return array
	 */
	public function getCssVariables()
	{
		return [];
	}
}