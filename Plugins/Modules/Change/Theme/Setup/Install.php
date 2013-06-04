<?php
namespace Change\Theme\Setup;

/**
 * Class Install
 * @package Change\Theme\Setup
 * @name \Change\Theme\Setup\Install
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

		$config->addPersistentEntry('Change/Events/ListenerAggregateClasses/Change_Theme',
				'\\Change\\Theme\\Events\\SharedListenerAggregate');

		$config->addPersistentEntry('Change/Admin/Listeners/Change_Theme',
				'\\Change\\Theme\\Admin\\Register');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}