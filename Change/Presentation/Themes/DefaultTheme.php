<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\Theme;
use Change\Presentation\PresentationServices;

class DefaultTheme implements Theme
{
	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var DefaultPageTemplate
	 */
	protected $defaultPageTemplate;

	/**
	 * @var string
	 */
	protected $vendor;

	/**
	 * @var string
	 */
	protected $shortName;

	/**
	 * @param PresentationServices $presentationServices
	 */
	function __construct($presentationServices)
	{
		$this->presentationServices = $presentationServices;
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
	 * @param ThemeManager $themeManager
	 * @return void
	 */
	public function setThemeManager(ThemeManager $themeManager)
	{
		if ($this->presentationServices === null)
		{
			$this->presentationServices = $themeManager->getPresentationServices();
		}
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
		return $this->presentationServices->getApplicationServices()->getApplication()->getWorkspace();
	}

	/**
	 * @var string $templateBasePath
	 */
	protected $templateBasePath;

	/**
	 * @return string
	 */
	public function getTemplateBasePath()
	{
		if ($this->templateBasePath === null)
		{
			$this->templateBasePath = $this->getWorkspace()->appPath('Themes', $this->vendor, $this->shortName);

			$as = $this->presentationServices->getApplicationServices();
			if ($as->getApplication()->inDevelopmentMode())
			{
				$pluginManager = $as->getPluginManager();
				$plugins = $pluginManager->getModules();
				foreach ($plugins as $plugin)
				{
					if ($plugin->isAvailable() && is_dir($plugin->getThemeAssetsPath()))
					{
						$this->presentationServices->getThemeManager()->installPluginTemplates($plugin);
					}
				}
			}
		}
		return $this->templateBasePath;
	}

	/**
	 * @var string $resourceBasePath
	 */
	protected $resourceBasePath;

	/**
	 * @return string
	 */
	public function getResourceBasePath()
	{
		if ($this->resourceBasePath === null)
		{
			$this->resourceBasePath = $this->getWorkspace()->pluginsThemesPath($this->vendor, $this->shortName, 'Assets');
			$as = $this->presentationServices->getApplicationServices();
			if ($as->getApplication()->inDevelopmentMode())
			{
				$pluginManager = $as->getPluginManager();
				$plugins = $pluginManager->getModules();
				foreach ($plugins as $plugin)
				{
					if ($plugin->isAvailable() && is_dir($plugin->getThemeAssetsPath()))
					{
						$this->presentationServices->getThemeManager()->installPluginTemplates($plugin);
					}
				}
			}
		}
		return $this->resourceBasePath;
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
	 * @return \Change\Presentation\Interfaces\PageTemplate
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
		$path = $this->getWorkspace()->composePath($this->getResourceBasePath(), str_replace('/', DIRECTORY_SEPARATOR, $resourcePath));
		return new FileResource($path);
	}

	/**
	 * @param string $assetPath
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	public function getAssetResource($assetPath)
	{
		$path = $this->getWorkspace()->composePath($this->getAssetBasePath(), str_replace('/', DIRECTORY_SEPARATOR, $assetPath));
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
			$pm = $this->presentationServices->getApplicationServices()->getPluginManager();
			$module = $pm->getModule($vendor, $moduleShortName);
			if ($module && $module->isAvailable())
			{
				return $this->getWorkspace()->composePath($module->getBasePath(), 'Assets', 'Theme', $resourceModulePath);
			}
		}
		return $this->getWorkspace()->pluginsThemesPath($this->vendor, $this->shortName, 'Assets', $resourcePath);
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
		$pluginManager = $this->presentationServices->getApplicationServices()->getPluginManager();
		$plugins = $pluginManager->getInstalledPlugins();
		foreach ($plugins as $plugin)
		{
			if ($plugin->isModule() && $plugin->isAvailable())
			{
				$configurationPath = $this->getWorkspace()->composePath($plugin->getBasePath(), 'Assets', 'Theme', 'assets.json');
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