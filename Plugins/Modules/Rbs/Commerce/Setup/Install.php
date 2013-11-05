<?php
namespace Rbs\Commerce\Setup;

/**
 * @name \Rbs\Commerce\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function initialize($plugin)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$configuration->addPersistentEntry('Change/Events/Http/Rest/Rbs_Commerce', '\Rbs\Commerce\Events\Http\Rest\Listeners');
		$configuration->addPersistentEntry('Change/Events/Http/Web/Rbs_Commerce', '\Rbs\Commerce\Events\Http\Web\Listeners');
		$configuration->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Commerce', '\Rbs\Commerce\Events\Admin\Listeners');
		$configuration->addPersistentEntry('Change/Events/CollectionManager/Rbs_Commerce', '\Rbs\Commerce\Events\CollectionManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/BlockManager/Rbs_Commerce', '\Rbs\Commerce\Events\BlockManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/CartManager/Rbs_Commerce', '\Rbs\Commerce\Events\CartManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/ProfileManager/Rbs_Commerce', '\Rbs\Commerce\Events\ProfileManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/JobManager/Rbs_Commerce', '\Rbs\Commerce\Events\JobManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/CrossSellingManager/Rbs_Commerce', '\Rbs\Commerce\Events\CrossSellingManager\Listeners');
		$configuration->addPersistentEntry('Change/Events/SeoManager/Rbs_Commerce', '\Rbs\Commerce\Events\SeoManager\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$applicationServices->getThemeManager()->installPluginTemplates($plugin);
		$schema = new Schema($applicationServices->getDbProvider()->getSchemaManager());
		$schema->generate();
		$applicationServices->getDbProvider()->closeConnection();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
