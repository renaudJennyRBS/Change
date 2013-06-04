<?php
namespace Change\Workflow\Setup;

/**
 * @name \Change\Workflow\Setup\Install
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

		$config->addPersistentEntry('Change/Events/ListenerAggregateClasses/Change_Workflow',
			'\\Change\\Workflow\\Events\\SharedListenerAggregate');
	}
}