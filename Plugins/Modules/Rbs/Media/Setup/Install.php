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

		$images = $config->getEntry('Change/Storage/images', array());
		$images = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalImageStorage',
			'basePath' => $application->getWorkspace()->appPath('Storage', 'images'),
			'formattedPath' => $application->getWorkspace()->cachePath('Imagestorage', 'images'),
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $images);
		$config->addPersistentEntry('Change/Storage/images', $images, \Change\Configuration\Configuration::INSTANCE);
		$config->addPersistentEntry('Change/Events/Http/Rest/Rbs_Media', '\\Rbs\\Media\\Http\\Rest\\ListenerAggregate');
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