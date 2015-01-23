<?php
namespace Rbs\Storeshipping\Setup;

/**
 * @name \Rbs\Storeshipping\Setup\Install
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
		$configuration->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Storeshipping',
			'\Rbs\Storeshipping\Events\SharedListeners');

		$configuration->addPersistentEntry('Rbs/Geo/Events/GeoManager/Rbs_Storeshipping',
			'\Rbs\Storeshipping\Events\GeoManager\Listeners');

		$configuration->addPersistentEntry('Change/Events/ProfileManager/Rbs_Storeshipping',
			'\Rbs\Storeshipping\User\Listeners');

		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Storeshipping',
			'\Rbs\Storeshipping\Blocks\Listeners');

		$configuration->addPersistentEntry('Change/Events/Http/Ajax/Rbs_Storeshipping',
			'\Rbs\Storeshipping\Events\Http\Ajax\Listeners');
	}

	public function executeDbSchema($plugin, $schemaManager)
	{
		parent::executeDbSchema($plugin, $schemaManager);
		$schema = new Schema($schemaManager);
		$schema->generate();
	}
}
