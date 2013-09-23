<?php
namespace Rbs\Media\Setup;

/**
 * @name \Rbs\Media\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $config
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $config)
	{
		$images = $config->getEntry('Change/Storage/images', array());
		$images = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalImageStorage',
			'basePath' => $application->getWorkspace()->appPath('Storage', 'images'),
			'formattedPath' => $application->getWorkspace()->cachePath('Imagestorage', 'images'),
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $images);
		$config->addPersistentEntry('Change/Storage/images', $images, \Change\Configuration\Configuration::INSTANCE);
	}


	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

}