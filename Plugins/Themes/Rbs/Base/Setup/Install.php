<?php
namespace Theme\Rbs\Base\Setup;

use Zend\Json\Json;

/**
 * @name \Theme\Rbs\Base\Setup
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
		$configuration = $themeManager->getDefault()->getAssetConfiguration();
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