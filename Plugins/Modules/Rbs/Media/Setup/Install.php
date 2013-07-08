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
		$images = array('class' => '\\Change\\Storage\\Engines\\LocalStorage',
			'basePath' => $application->getWorkspace()->appPath('Storage', 'images'),
			'useDBStat' => true, 'baseURL' => false
		);
		$config->addPersistentEntry('Change/Storage/images', $images, \Change\Configuration\Configuration::INSTANCE);

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