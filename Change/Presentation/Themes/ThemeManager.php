<?php
namespace Change\Presentation\Themes;

use Change\Events\EventsCapableTrait;
use Change\Presentation\Interfaces\Theme;
use Change\Events\Event;

/**
 * @api
 * @name \Change\Presentation\Themes\ThemeManager
 */
class ThemeManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const DEFAULT_THEME_NAME = 'Rbs_Base';
	const EVENT_LOADING = 'loading';
	const EVENT_MAIL_TEMPLATE_LOADING = 'mail.template.loading';

	const EVENT_MANAGER_IDENTIFIER = 'Presentation.Themes';

	/**
	 * @var Theme
	 */
	protected $default;

	/**
	 * @var Theme
	 */
	protected $current;

	/**
	 * @var Theme[]
	 */
	protected $themes = array();

	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;

	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;

	/**
	 * @param \Change\Configuration\Configuration $configuration
	 * @return $this
	 */
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
		return $this;
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @param \Change\Workspace $workspace
	 * @return $this
	 */
	public function setWorkspace(\Change\Workspace $workspace)
	{
		$this->workspace = $workspace;
		return $this;
	}

	/**
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->workspace;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Change/Events/ThemeManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_LOADING, array($this, 'onLoading'), 5);
	}

	/**
	 * @param string $themeName
	 * @return Theme|null
	 */
	protected function dispatchLoading($themeName)
	{
		$event = new Event(static::EVENT_LOADING, $this, array('themeName' => $themeName));
		$callback = function ($result)
		{
			return ($result instanceof Theme);
		};
		$results = $this->getEventManager()->triggerUntil($event, $callback);
		return ($results->stopped() && ($results->last() instanceof Theme)) ? $results->last() : $event->getParam('theme');
	}

	/**
	 * @param Event $event
	 */
	public function onLoading(Event $event)
	{
		if ($event->getParam('themeName') === static::DEFAULT_THEME_NAME)
		{
			$defaultTheme = new DefaultTheme($event->getApplication());
			$defaultTheme->setPluginManager($event->getApplicationServices()->getPluginManager())
				->setThemeManager($this);
			$event->setParam('theme', $defaultTheme);
		}
	}

	/**
	 * @param Theme $current
	 */
	public function setCurrent(Theme $current = null)
	{
		$this->current = $current;
		if ($current !== null)
		{
			$this->addTheme($current);
		}
	}

	/**
	 * @return Theme
	 */
	public function getCurrent()
	{
		return $this->current !== null ? $this->current : $this->getDefault();
	}

	/**
	 * @throws \RuntimeException
	 * @return Theme
	 */
	public function getDefault()
	{
		if ($this->default === null)
		{
			$this->default = $this->getByName(static::DEFAULT_THEME_NAME);
			if ($this->default === null)
			{
				throw new \RuntimeException('Theme ' . static::DEFAULT_THEME_NAME . ' not found', 999999);
			}
		}
		return $this->default;
	}

	/**
	 * @param string $name
	 * @return Theme|null
	 */
	public function getByName($name)
	{
		if ($name === null)
		{
			return $this->getCurrent();
		}
		elseif (!array_key_exists($name, $this->themes))
		{
			$theme = $this->dispatchLoading($name);
			if ($theme instanceof Theme)
			{
				$this->addTheme($theme);
			}
			else
			{
				$this->themes[$name] = null;
			}
		}
		return $this->themes[$name];
	}

	/**
	 * @param Theme $theme
	 */
	public function addTheme(Theme $theme)
	{
		$this->themes[$theme->getName()] = $theme;
		$theme->setThemeManager($this);
		$parentTheme = $theme->getParentTheme();
		if ($parentTheme && !isset($this->themes[$parentTheme->getName()]))
		{
			$this->addTheme($parentTheme);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param Theme|null $theme
	 */
	public function installPluginTemplates($plugin, $theme = null)
	{
		$path = $plugin->getTwigAssetsPath();
		if (!$path)
		{
			return;
		}
		if ($theme === null)
		{
			$theme = $this->getDefault();
		}
		else
		{
			$theme->setThemeManager($this);
		}

		$includedExtensions = ['twig'];
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,
			\FilesystemIterator::CURRENT_AS_SELF + \FilesystemIterator::SKIP_DOTS));
		while ($it->valid())
		{
			/* @var $current \RecursiveDirectoryIterator */
			$current = $it->current();
			if ($current->isFile() && strpos($current->getBasename(), '.') !== 0
				&& in_array($current->getExtension(), $includedExtensions)
			)
			{
				$moduleName = $plugin->isTheme() ? null : $plugin->getName();
				$theme->installTemplateContent($moduleName, $current->getSubPathname(),
					file_get_contents($current->getPathname()));
			}
			$it->next();
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param Theme|null $theme
	 */
	public function installPluginAssets($plugin, $theme = null)
	{
		$workspace = $this->getWorkspace();
		$path = $plugin->getThemeAssetsPath();
		if (!is_dir($path))
		{
			return;
		}
		if ($theme === null)
		{
			$theme = $this->getDefault();
		}
		else
		{
			$theme->setThemeManager($this);
		}
		$excludedExtensions = ['js', 'json', 'map', 'css', 'twig', 'less'];
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,
			\FilesystemIterator::CURRENT_AS_SELF + \FilesystemIterator::SKIP_DOTS));
		while ($it->valid())
		{
			/* @var $current \RecursiveDirectoryIterator */
			$current = $it->current();
			if ($current->isFile() && strpos($current->getBasename(), '.') !== 0
				&& !in_array($current->getExtension(), $excludedExtensions)
			)
			{
				$moduleName = $plugin->isTheme() ? null : $plugin->getName();
				$path = $workspace->composePath($this->getAssetRootPath(), 'Theme', str_replace('_', '/', $theme->getName()),
					$moduleName, $current->getSubPathname());
				\Change\Stdlib\File::mkdir(dirname($path));
				file_put_contents($path, file_get_contents($current->getPathname()));
			}
			$it->next();
		}
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getThemeTwigBasePaths()
	{
		$paths = array();
		$theme = $this->getCurrent();
		while (true)
		{
			$basePath = $theme->getTemplateBasePath();
			if (is_dir($basePath))
			{
				$paths[] = $basePath;
			}

			if ($theme === $this->getDefault())
			{
				break;
			}
			elseif ($theme->getParentTheme())
			{
				$theme = $theme->getParentTheme();
			}
			else
			{
				$theme = $this->getDefault();
			}
		}
		return $paths;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function normalizeAssetName($name)
	{
		return str_replace(['/', '.', '-'], '', $name);
	}

	/**
	 * @param array $configuration
	 * @return \Assetic\AssetManager
	 */
	public function getAsseticManager($configuration)
	{
		$am = new \Assetic\AssetManager();
		foreach ($configuration as $block)
		{
			foreach ($block as $assetType)
			{
				foreach ($assetType as $assetUrl)
				{
					if (preg_match('/^Theme\/([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/(.+)$/', $assetUrl, $matches))
					{
						$themeVendor = $matches[1];
						$themeShortName = $matches[2];
						$path = $matches[3];
						$theme = $this->getByName($themeVendor . '_' . $themeShortName);
						if ($theme)
						{
							$resourceFilePath = $theme->getResourceFilePath($path);
							if (file_exists($resourceFilePath))
							{
								$asset = new \Assetic\Asset\FileAsset($resourceFilePath);
								if (substr($resourceFilePath, -4) === '.css')
								{
									$filter = new \Change\Presentation\Themes\CssVarFilter($theme->getCssVariables());
									$asset->ensureFilter($filter);
								}
								elseif (substr($resourceFilePath, -5) === '.less')
								{
									$filter = new \Assetic\Filter\LessphpFilter();
									$asset->ensureFilter($filter);
									$asset->setTargetPath($assetUrl . '.css');
								}
								if (!$asset->getTargetPath())
								{
									$asset->setTargetPath($assetUrl);
								}
								$name = $this->normalizeAssetName($assetUrl);
								$am->set($name, $asset);
							}
						}
					}
				}
			}
		}
		return $am;
	}

	/**
	 * @param array $configuration
	 * @param string[] $blockNames
	 * @return \Assetic\Asset\AssetCollection
	 */
	public function getJsAssetNames($configuration, $blockNames)
	{
		$names = [];
		if (isset($configuration['*']['jsAssets']) && is_array($configuration['*']['jsAssets']))
		{
			foreach ($configuration['*']['jsAssets'] as $themeJsAsset)
			{
				$names[] = $this->normalizeAssetName($themeJsAsset);
			}
		}


		foreach (array_keys($blockNames) as $blockName)
		{
			if (isset($configuration[$blockName]['jsAssets']) && is_array($configuration[$blockName]['jsAssets']))
			{
				foreach ($configuration[$blockName]['jsAssets'] as $blockJsAsset)
				{
					$names[] = $this->normalizeAssetName($blockJsAsset);
				}
			}
		}
		return array_unique($names);
	}

	/**
	 * @param array $configuration
	 * @param string[] $blockNames
	 * @return \Assetic\Asset\AssetCollection
	 */
	public function getCssAssetNames($configuration, $blockNames)
	{
		$names = [];
		if (isset($configuration['*']['cssAssets']) && is_array($configuration['*']['cssAssets']))
		{
			foreach ($configuration['*']['cssAssets'] as $themeCssAsset)
			{
				$names[] = $this->normalizeAssetName($themeCssAsset);
			}
		}


		foreach (array_keys($blockNames) as $blockName)
		{
			if (isset($configuration[$blockName]))
			{
				if (isset($configuration[$blockName]['cssAssets']) && is_array($configuration[$blockName]['cssAssets']))
				{
					foreach ($configuration[$blockName]['cssAssets'] as $blockCssAsset)
					{
						$names[] = $this->normalizeAssetName($blockCssAsset);
					}
				}
			}
		}
		return array_unique($names);
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function getAssetRootPath()
	{
		$root = $this->getConfiguration()->getEntry('Change/Install/webBaseDirectory', false);
		if ($root === false)
		{
			throw new \RuntimeException('Change/Install/webBaseDirectory not defined', 999999);
		}
		return $this->getWorkspace()->composeAbsolutePath($root, 'Assets');
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function getAssetBaseUrl()
	{
		$webBaseURLPath = $this->getConfiguration()->getEntry('Change/Install/webBaseURLPath', false);
		if ($webBaseURLPath === false)
		{
			throw new \RuntimeException('Change/Install/webBaseURLPath not defined', 999999);
		}
		return $webBaseURLPath . '/Assets/';
	}
}