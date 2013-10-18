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
		$configuration->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Event', '\Rbs\Event\Events\Admin\Listeners');
		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Event', '\Rbs\Event\Events\BlockManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/CollectionManager/Rbs_Event',
			'\Rbs\Event\Events\CollectionManager\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$presentationServices->getThemeManager()->installPluginTemplates($plugin);
	}
}
