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
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$images = $configuration->getEntry('Change/Storage/images', array());
		$images = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalImageStorage',
			'basePath' => $application->getWorkspace()->appPath('Storage', 'images'),
			'formattedPath' => $application->getWorkspace()->cachePath('Imagestorage', 'images'),
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $images);
		$configuration->addPersistentEntry('Change/Storage/images', $images, \Change\Configuration\Configuration::INSTANCE);
	}


	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

}