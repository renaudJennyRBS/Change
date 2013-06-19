<?php
namespace Rbs\Collection\Setup;

/**
 * @name \Rbs\Collection\Setup\Install
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

		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Collection',
			'\\Rbs\\Collection\\Admin\\Register');

		$config->addPersistentEntry('Change/Events/CollectionManager/Rbs_Collection',
			'\\Rbs\\Collection\\Events\\ListenerAggregate');
	}
}
