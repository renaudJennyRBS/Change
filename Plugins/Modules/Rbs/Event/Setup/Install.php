<?php
namespace Rbs\Event\Setup;

/**
 * @name \Rbs\Event\Setup\Install
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
		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Event', '\Rbs\Event\Events\BlockManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/CollectionManager/Rbs_Event',
			'\Rbs\Event\Events\CollectionManager\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$applicationServices->getThemeManager()->installPluginTemplates($plugin);
	}
}
