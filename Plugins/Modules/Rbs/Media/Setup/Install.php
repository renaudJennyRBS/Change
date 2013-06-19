<?php
namespace Rbs\Media\Setup;

/**
 * @name \Rbs\Media\Setup\Install
 */
class Install
{

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application)
	{
		/* @var $config \Change\Configuration\EditableConfiguration */
		$config = $application->getConfiguration();

		$config->addPersistentEntry('Change/Storage/default/class', '\\Change\\Storage\\Engines\\LocalStorage');
		$config->addPersistentEntry('Change/Storage/default/basePath', $application->getWorkspace()->appPath('Storage'));
		$config->addPersistentEntry('Change/Storage/default/useDBStat', true);
		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Media', '\\Rbs\\Media\\Admin\\Register');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}