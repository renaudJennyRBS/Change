<?php
namespace Rbs\Elasticsearch\Setup;

/**
 * @name \Rbs\Elasticsearch\Setup\Install
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
		$configuration->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Elasticsearch',
			'\Rbs\Elasticsearch\Events\SharedListeners');

		$configuration->addPersistentEntry('Rbs/Elasticsearch/Events/IndexManager/Rbs_Elasticsearch',
			'\Rbs\Elasticsearch\Index\Listeners');

		$configuration->addPersistentEntry('Change/Events/Commands/Rbs_Elasticsearch',
			'\Rbs\Elasticsearch\Commands\Listeners');

		$configuration->addPersistentEntry('Change/Events/JobManager/Rbs_Elasticsearch',
			'\Rbs\Elasticsearch\Job\Listeners');

		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Elasticsearch',
			'\Rbs\Elasticsearch\Blocks\Listeners');

		$configuration->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Elasticsearch',
			'\Rbs\Elasticsearch\Admin\Listeners');

		$configuration->addPersistentEntry('Change/Events/CollectionManager/Rbs_Elasticsearch',
			'\Rbs\Elasticsearch\Collection\Listeners');
	}
}
