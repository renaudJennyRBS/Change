<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Themes;

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
	const EVENT_GET_ASSET_CONFIGURATION = 'getAssetConfiguration';

	const EVENT_ADD_PAGE_RESOURCES = 'addPageResources';

	/**
	 * @var Theme
	 */
	protected $default;

	/**
	 * @var Theme
	 */
	protected $current;

	/**
	 * @var boolean
	 */
	protected $combineAssets;

	/**
	 * @var Theme[]
	 */
	protected $themes = [];

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getApplication()->getConfiguration();
	}

	/**
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->getApplication()->getWorkspace();
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
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/ThemeManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_LOADING, [$this, 'onLoading'], 5);
		$eventManager->attach(static::EVENT_GET_ASSET_CONFIGURATION, [$this, 'onDefaultGetAssetConfiguration'], 10);
		$eventManager->attach(static::EVENT_GET_ASSET_CONFIGURATION, [$this, 'onDefaultCompileGetAssetConfiguration'], 5);
		$eventManager->attach(static::EVENT_ADD_PAGE_RESOURCES, [$this, 'onDefaultAddPageResources'], 5);
	}

	/**
	 * @return boolean|null
	 */
	public function getCombineAssets()
	{
		if ($this->combineAssets === null)
		{
			$this->combineAssets = $this->getApplication()->getConfiguration()->getEntry('Change/Http/Web/combineAssets');
		}
		return $this->combineAssets;
	}

	/**
	 * @@param boolean|null $value
	 * @return $this
	 */
	public function setCombineAssets($value)
	{
		$this->combineAssets = $value;
		return $this;
	}

	/**
	 * @param string $themeName
	 * @return Theme|null
	 */
	protected function dispatchLoading($themeName)
	{
		$event = new Event(static::EVENT_LOADING, $this, ['themeName' => $themeName]);
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
	 * @return \Change\Presentation\Themes\DefaultTheme
	 */
	public function getDefault()
	{
		if ($this->default === null)
		{
			$this->default = $this->getByName(static::DEFAULT_THEME_NAME);
			if (!($this->default instanceof \Change\Presentation\Themes\DefaultTheme))
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
		if ($theme === null)
		{
			if ($plugin->isTheme())
			{
				return;
			}
			$theme = $this->getDefault();
		}
		else
		{
			$theme->setThemeManager($this);
			$theme->removeTemplatesContent(null);
		}

		$moduleName = $plugin->isTheme() ? null : $plugin->getName();
		if ($moduleName)
		{
			$theme->removeTemplatesContent($moduleName);
		}

		$path = $plugin->getTwigAssetsPath();
		if (!$path)
		{
			return;
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
		$srcAssetPath = $plugin->getThemeAssetsPath();
		if (!is_dir($srcAssetPath))
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

		$moduleName = $plugin->isTheme() ? null : $plugin->getName();
		$targetAssetRootPath = $workspace->composePath($this->getAssetRootPath(), 'Theme',
			str_replace('_', '/', $theme->getName()), $moduleName);

		$excludedExtensions = ['js', 'json', 'css', 'twig', 'less'];
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcAssetPath,
			\FilesystemIterator::CURRENT_AS_SELF + \FilesystemIterator::SKIP_DOTS));
		while ($it->valid())
		{
			/* @var $current \RecursiveDirectoryIterator */
			$current = $it->current();
			if ($current->isFile() && strpos($current->getBasename(), '.') !== 0
				&& !in_array($current->getExtension(), $excludedExtensions)
			)
			{
				$targetAssetPath = $workspace->composePath($targetAssetRootPath, $current->getSubPathname());
				\Change\Stdlib\File::mkdir(dirname($targetAssetPath));
				file_put_contents($targetAssetPath, file_get_contents($current->getPathname()));
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
		$paths = [];
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
	 * @param \Change\Presentation\Interfaces\Theme $theme
	 * @return \Assetic\AssetManager
	 */
	public function getAsseticManager($configuration, $theme)
	{
		if ($this->getCombineAssets())
		{
			return $this->getCompressedAsseticManager($configuration, $theme);
		}

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
						$assetTheme = $this->getByName($themeVendor . '_' . $themeShortName);
						if ($assetTheme)
						{
							$resourceFilePath = $assetTheme->getResourceFilePath($path);
							if (file_exists($resourceFilePath))
							{
								$asset = new \Assetic\Asset\FileAsset($resourceFilePath);
								if (substr($resourceFilePath, -5) === '.less')
								{
									$cacheDir = $this->getApplication()->getWorkspace()->cachePath('less');
									\Change\Stdlib\File::mkdir($cacheDir);

									$filter = new AsseticLessFilter($cacheDir);
									if ($this->getApplication()->inDevelopmentMode())
									{
										$filter->setFormatter('classic');
									}
									else
									{
										$filter->setFormatter('compressed');
									}
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
	 * @param \Change\Presentation\Interfaces\Theme $theme
	 * @return \Assetic\AssetManager
	 */
	protected function getCompressedAsseticManager($configuration, $theme)
	{
		$am = new \Assetic\AssetManager();

		$baseUrl = 'Theme/' . str_replace('_', '/', $theme->getName()) . '/';
		$jsAssets = new \Assetic\Asset\AssetCollection();
		$jsAssets->setTargetPath($baseUrl . 'blocks.js');
		$cssAssets = new \Assetic\Asset\AssetCollection();
		$cssAssets->setTargetPath($baseUrl . 'blocks.css');

		foreach ($configuration as $treeName => $block)
		{
			foreach ($block as $assetType => $assetList)
			{
				foreach ($assetList as $assetUrl)
				{
					if (preg_match('/^Theme\/([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/(.+)$/', $assetUrl, $matches))
					{
						$themeVendor = $matches[1];
						$themeShortName = $matches[2];
						$path = $matches[3];
						$assetTheme = $this->getByName($themeVendor . '_' . $themeShortName);
						if ($assetTheme)
						{
							$resourceFilePath = $assetTheme->getResourceFilePath($path);
							if (file_exists($resourceFilePath))
							{
								$asset = new \Assetic\Asset\FileAsset($resourceFilePath);
								if (substr($resourceFilePath, -5) === '.less')
								{
									$cacheDir = $this->getApplication()->getWorkspace()->cachePath('less');
									\Change\Stdlib\File::mkdir($cacheDir);

									$filter = new AsseticLessFilter($cacheDir);
									if ($this->getApplication()->inDevelopmentMode())
									{
										$filter->setFormatter('classic');
									}
									else
									{
										$filter->setFormatter('compressed');
									}
									$asset->ensureFilter($filter);
									$asset->setTargetPath($assetUrl . '.css');
								}

								if (strpos($treeName, '*') === 0)
								{
									if (!$asset->getTargetPath())
									{
										$asset->setTargetPath($assetUrl);
									}
									$name = $this->normalizeAssetName($assetUrl);
									$am->set($name, $asset);
								}
								else
								{
									if ($assetType == 'jsAssets')
									{
										$jsAssets->add($asset);
									}
									elseif ($assetType == 'cssAssets')
									{
										$cssAssets->add($asset);
									}
								}
							}
						}
					}
				}
			}
		}

		if (count($jsAssets->all()))
		{
			$devMode = $this->getApplication()->inDevelopmentMode();
			if (!$devMode)
			{
				$jsAssets->ensureFilter(new \Assetic\Filter\JSMinFilter());
			}
			$am->set('blocksJs', $jsAssets);
		}

		if (count($cssAssets->all()))
		{
			$am->set('blocksCss', $cssAssets);
		}

		return $am;
	}

	/**
	 * @param Theme $theme
	 * @param string $themeResourcePath
	 * @return \Change\Presentation\Interfaces\ThemeResource|null
	 */
	public function getResource(Theme $theme, $themeResourcePath)
	{
		if (!is_string($themeResourcePath))
		{
			return null;
		}

		$assetRootPath = $this->getAssetRootPath();
		$themePath = str_replace('_', '/', $theme->getName());
		$filePath = $this->getApplication()->getWorkspace()->composePath($assetRootPath, 'Theme', $themePath, $themeResourcePath);
		$resource = new \Change\Presentation\Themes\FileResource($filePath);
		if ($resource->isValid())
		{
			return $resource;
		}
		return $theme->getResource($themeResourcePath);
	}

	/**
	 * @param array $configuration
	 * @param string[] $blockNames
	 * @param string $pageTemplateCode
	 * @return \Assetic\Asset\AssetCollection
	 */
	public function getJsAssetNames($configuration, $blockNames, $pageTemplateCode)
	{
		$names = [];
		if ($pageTemplateCode && isset($configuration['*'.$pageTemplateCode.'*']['jsAssets']) && is_array($configuration['*'.$pageTemplateCode.'*']['jsAssets']))
		{
			foreach ($configuration['*'.$pageTemplateCode.'*']['jsAssets'] as $themeJsAsset)
			{
				$names[] = $this->normalizeAssetName($themeJsAsset);
			}
		}
		else if (isset($configuration['*']['jsAssets']) && is_array($configuration['*']['jsAssets']))
		{
			foreach ($configuration['*']['jsAssets'] as $themeJsAsset)
			{
				$names[] = $this->normalizeAssetName($themeJsAsset);
			}
		}

		if ($this->getCombineAssets())
		{
			$names[] = 'blocksJs';
		}
		else
		{
			foreach (array_keys($blockNames) as $blockName)
			{
				if (isset($configuration[$blockName]['jsAssets'])
					&& is_array($configuration[$blockName]['jsAssets'])
				)
				{
					foreach ($configuration[$blockName]['jsAssets'] as $blockJsAsset)
					{
						$names[] = $this->normalizeAssetName($blockJsAsset);
					}
				}
			}
		}

		return array_unique($names);
	}

	/**
	 * @param array $configuration
	 * @param string[] $blockNames
	 * @param string $pageTemplateCode
	 * @return \Assetic\Asset\AssetCollection
	 */
	public function getCssAssetNames($configuration, $blockNames, $pageTemplateCode)
	{
		$names = [];
		if ($pageTemplateCode && isset($configuration['*'.$pageTemplateCode.'*']['cssAssets']) && is_array($configuration['*'.$pageTemplateCode.'*']['cssAssets']))
		{
			foreach ($configuration['*'.$pageTemplateCode.'*']['cssAssets'] as $themeCssAsset)
			{
				$names[] = $this->normalizeAssetName($themeCssAsset);
			}
		}
		else if (isset($configuration['*']['cssAssets']) && is_array($configuration['*']['cssAssets']))
		{
			foreach ($configuration['*']['cssAssets'] as $themeCssAsset)
			{
				$names[] = $this->normalizeAssetName($themeCssAsset);
			}
		}

		if ($this->getCombineAssets())
		{
			$names[] = 'blocksCss';
		}
		else
		{
			foreach (array_keys($blockNames) as $blockName)
			{
				if (isset($configuration[$blockName]))
				{
					if (isset($configuration[$blockName]['cssAssets'])
						&& is_array($configuration[$blockName]['cssAssets'])
					)
					{
						foreach ($configuration[$blockName]['cssAssets'] as $blockCssAsset)
						{
							$names[] = $this->normalizeAssetName($blockCssAsset);
						}
					}
				}
			}
		}

		return array_unique($names);
	}

	/**
	 * @param \Change\Presentation\Interfaces\Theme $theme
	 * @return array|mixed
	 */
	public function getAssetConfiguration($theme)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['theme' => $theme]);
		$this->getEventManager()->trigger(static::EVENT_GET_ASSET_CONFIGURATION, $this, $args);
		if (isset($args['configuration']))
		{
			return $args['configuration'];
		}

		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetAssetConfiguration($event)
	{
		$theme = $event->getParam('theme');
		if ($theme instanceof \Change\Presentation\Interfaces\Theme)
		{
			$defaultTheme = $this->getDefault();
			$configurationRules = $defaultTheme->getAssetConfiguration();

			$types = ['templates' => [], 'blocks' => [], 'blocksExtend' => [], 'jsCollections' => []];
			$configurationRules += $types;

			if ($theme === $defaultTheme)
			{
				$event->setParam('configurationRules', $configurationRules);
			}
			else
			{
				$rules = [];

				while ($theme)
				{
					$rules[] = $theme->getAssetConfiguration() + $types;
					$theme = $theme->getParentTheme();
				}

				foreach (array_reverse($rules) as $rule)
				{
					$configurationRules = $this->appendConfiguration($configurationRules, $rule);
				}

				$event->setParam('configurationRules', $configurationRules);
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultCompileGetAssetConfiguration($event)
	{
		$configurationRules = $event->getParam('configurationRules');

		$configuration = [];
		$LCID = $event->getApplicationServices()->getI18nManager()->getLCID();
		$i18nFiles = null;
		if (isset($configurationRules['templates']['i18n_' . $LCID]))
		{
			$i18nFiles = $configurationRules['templates']['i18n_' . $LCID];
		}

		foreach ($configurationRules['templates'] as $templateName => $templateConfiguration)
		{
			if ($i18nFiles && strpos($templateName, 'i18n_') !== 0)
			{
				$templateConfiguration = array_merge_recursive($templateConfiguration, $i18nFiles);
			}

			if ($templateName != '*')
			{
				$templateName = '*' . $templateName . '*';
			}
			$configuration[$templateName] = $templateConfiguration;
		}

		foreach ($configurationRules['blocksExtend'] as $extendBlockName => $extendBlockConfiguration)
		{
			if (isset($configurationRules['blocks'][$extendBlockName]))
			{
				$configurationRules['blocks'][$extendBlockName] = array_merge_recursive($configurationRules['blocks'][$extendBlockName],
					$extendBlockConfiguration);
			}
		}

		foreach ($configurationRules['blocks'] as $blockName => $blockConfiguration)
		{
			if (isset($blockConfiguration['jsCollections']))
			{
				foreach ($blockConfiguration['jsCollections'] as $jsCollection)
				{
					if (isset($configurationRules['jsCollections'][$jsCollection]))
					{
						$jsAssets = [];
						if (isset($blockConfiguration['jsAssets']))
						{
							$jsAssets = $blockConfiguration['jsAssets'];
						}

						foreach ($configurationRules['jsCollections'][$jsCollection] as $jsCollectionFile)
						{
							$jsAssets[] = $jsCollectionFile;
						}
						$blockConfiguration['jsAssets'] = $jsAssets;
					}
				}
			}

			$jsAssets = [];
			$cssAssets = [];
			if (isset($blockConfiguration['jsAssets']))
			{
				$jsAssets = $blockConfiguration['jsAssets'];
			}
			if (isset($blockConfiguration['cssAssets']))
			{
				$cssAssets = $blockConfiguration['cssAssets'];
			}

			$configuration[$blockName] = ['jsAssets' => $jsAssets, 'cssAssets' => $cssAssets];
		}

		$event->setParam('configuration', $configuration);
	}

	/**
	 * @param array $parentRules
	 * @param array $rule
	 * @return array
	 */
	public function appendConfiguration($parentRules, $rule)
	{
		$parentRules['templates'] = array_merge($parentRules['templates'], $rule['templates']);
		$parentRules['blocks'] = array_merge($parentRules['blocks'], $rule['blocks']);
		$parentRules['jsCollections'] = array_merge_recursive($parentRules['jsCollections'], $rule['jsCollections']);
		$parentRules['blocksExtend'] = array_merge_recursive($parentRules['blocksExtend'], $rule['blocksExtend']);

		return $parentRules;
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

	/**
	 * @api
	 * @param \Change\Http\Web\Result\Page $pageResult
	 * @param \Change\Presentation\Interfaces\Template $template
	 * @param \Change\Presentation\Layout\Block[] $blocks
	 */
	public function addPageResources(\Change\Http\Web\Result\Page $pageResult,
		\Change\Presentation\Interfaces\Template $template, array $blocks)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['pageResult' => $pageResult, 'template' => $template, 'blocks' => $blocks]);
		$eventManager->trigger(static::EVENT_ADD_PAGE_RESOURCES, $this, $args);
	}

	public function onDefaultAddPageResources(\Change\Events\Event $event) {

		/** @var \Change\Http\Web\Result\Page $result */
		$result = $event->getParam('pageResult');

		/** @var \Change\Presentation\Layout\Block[] $blocks */
		$blocks = $event->getParam('blocks');

		/** @var \Change\Presentation\Interfaces\Template $template */
		$template = $event->getParam('template');

		$blockNames = [];
		foreach($blocks as $block)
		{
			$blockName = $block->getName();
			$blockNames[$blockName] = $blockName;
		}

		$configuration = $this->getAssetConfiguration($this->getCurrent());
		$asseticManager = $this->getAsseticManager($configuration, $template->getTheme());

		$event->setParam('configuration', $configuration);

		if ($this->getApplication()->inDevelopmentMode())
		{
			(new \Assetic\AssetWriter($this->getAssetRootPath()))->writeManagerAssets($asseticManager);
		}

		$cssNames = $this->getCssAssetNames($configuration, $blockNames, $template->getCode());
		foreach($cssNames as $cssName)
		{
			try
			{
				$a = $asseticManager->get($cssName);
				$result->addCssAsset($a->getTargetPath());
			}
			catch (\Exception $e)
			{
				$logging = $this->getApplication()->getLogging();
				$logging->warn('asset resource name not found: ' . $cssName);
				$logging->exception($e);
			}
		}

		$jsNames = $this->getJsAssetNames($configuration, $blockNames, $template->getCode());
		foreach ($jsNames as $jsName)
		{
			try
			{
				$a = $asseticManager->get($jsName);
				$result->addJsAsset($a->getTargetPath());
			}
			catch (\Exception $e)
			{
				$logging = $this->getApplication()->getLogging();
				$logging->warn('asset resource name not found: ' . $jsName);
				$logging->exception($e);
			}
		}
	}
}