<?php
namespace Rbs\Productreturn\Setup;

/**
 * @name \Rbs\Productreturn\Setup\Install
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
		$images = $configuration->getEntry('Change/Storage/Rbs_Productreturn', array());
		$images = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalStorage',
			'basePath' => 'App/Storage/Rbs_Productreturn/files',
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $images);
		$configuration->addPersistentEntry('Change/Storage/Rbs_Productreturn', $images);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @throws \RuntimeException
	 */
	public function executeDbSchema($plugin, $schemaManager)
	{
		$schema = new Schema($schemaManager);
		$schema->generate();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
