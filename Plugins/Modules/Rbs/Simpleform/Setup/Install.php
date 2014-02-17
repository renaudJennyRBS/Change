<?php
namespace Rbs\Simpleform\Setup;

/**
 * @name \Rbs\Simpleform\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$images = $configuration->getEntry('Change/Storage/Rbs_Simpleform', array());
		$images = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalStorage',
			'basePath' => 'App/Storage/Rbs_Simpleform/files',
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $images);
		$configuration->addPersistentEntry('Change/Storage/Rbs_Simpleform', $images);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
