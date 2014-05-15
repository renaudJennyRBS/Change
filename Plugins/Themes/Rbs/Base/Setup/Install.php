<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Themes\Rbs\Base\Setup;

/**
 * @name \Themes\Rbs\Base\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$themeManager = $applicationServices->getThemeManager();
		$pluginManager = $applicationServices->getPluginManager();
		$modules = $pluginManager->getModules();
		$themeManager->installPluginTemplates($plugin);
		$themeManager->installPluginAssets($plugin);
		foreach ($modules as $module)
		{
			if ($module->isAvailable())
			{
				//echo $module, PHP_EOL;
				$themeManager->installPluginTemplates($module);
				$themeManager->installPluginAssets($module);
			}
		}

		$this->writeAssetic($themeManager->getDefault(), $themeManager);
	}

	/**
	 * @param \Change\Presentation\Themes\DefaultTheme $theme
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 */
	protected function writeAssetic($theme, $themeManager)
	{
		$configuration = $theme->getAssetConfiguration();
		$am = $themeManager->getAsseticManager($configuration);
		$writer = new \Assetic\AssetWriter($themeManager->getAssetRootPath());
		$writer->writeManagerAssets($am);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}