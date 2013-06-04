<?php
namespace Theme\Change\Base\Setup;

/**
 * @name \Theme\Change\Base\Setup
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}