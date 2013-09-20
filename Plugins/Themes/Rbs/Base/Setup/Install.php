<?php
namespace Theme\Rbs\Base\Setup;

/**
 * @name \Theme\Rbs\Base\Setup
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$pluginManager = $applicationServices->getPluginManager();
		$plugins = $pluginManager->getModules();
		$presentationServices->getThemeManager()->installPluginTemplates($plugin);
		foreach ($plugins as $plugin)
		{
			if ($plugin->isAvailable() && is_dir($plugin->getThemeAssetsPath()))
			{
				$presentationServices->getThemeManager()->installPluginTemplates($plugin);
			}
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}