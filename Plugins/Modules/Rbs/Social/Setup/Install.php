<?php
namespace Rbs\Social\Setup;

/**
 * @name \Rbs\Social\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
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
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Social', '\Rbs\Social\Events\BlockManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/Http/Rest/Rbs_Social', '\Rbs\Social\Events\Http\Rest\Listeners');
		$configuration->addPersistentEntry('Rbs/Admin/Events/AdminManager/Rbs_Social', '\Rbs\Social\Events\AdminManager\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$applicationServices->getThemeManager()->installPluginTemplates($plugin);
	}
}
