<?php
namespace Rbs\Dev\Setup;

/**
 * @name \Rbs\Dev\Setup\Install
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
		$configuration->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Dev',
			'\Rbs\Dev\Events\SharedListeners');

		$configuration->addPersistentEntry('Change/Events/Commands/Rbs_Dev', '\Rbs\Dev\Events\Commands\Listeners');
	}

}
