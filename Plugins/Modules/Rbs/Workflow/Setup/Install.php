<?php
namespace Rbs\Workflow\Setup;

/**
 * @name \Rbs\Workflow\Setup\Install
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

		$config->addPersistentEntry('Change/Events/WorkflowManager/Rbs_Workflow',
			'\\Rbs\\Workflow\\Events\\ListenerAggregate');
	}
}