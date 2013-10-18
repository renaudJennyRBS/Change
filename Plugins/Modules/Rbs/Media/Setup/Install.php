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
		$webBaseDirectory = $configuration->getEntry('Change/Install/webBaseDirectory', '');
		if (!empty($webBaseDirectory))
		{
			$formattedPath = $application->getWorkspace()->composePath($webBaseDirectory, 'Imagestorage', 'images');
		}
		else
		{
			$formattedPath = $application->getWorkspace()->composePath('Imagestorage', 'images');
		}
		$images = $configuration->getEntry('Change/Storage/images', array());
		$images = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalImageStorage',
			'basePath' => 'App/Storage/images',
			'formattedPath' => $formattedPath,
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $images);
		$configuration->addPersistentEntry('Change/Storage/images', $images);
	}


	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

}