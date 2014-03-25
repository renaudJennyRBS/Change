<?php
namespace Rbs\Wishlist\Setup;

/**
 * @name \Rbs\Wishlist\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$applicationServices->getThemeManager()->installPluginTemplates($plugin);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
