<?php
namespace Rbs\Commerce\Setup;

/**
 * @name \Rbs\Commerce\Setup\Install
 */
class Install
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
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application)
	{
		/* @var $config \Change\Configuration\EditableConfiguration */
		$config = $application->getConfiguration();

        $config->addPersistentEntry('Change/Events/Http/Rest/Rbs_Commerce', '\Rbs\Commerce\Events\Http\Rest\Listeners');
		$config->addPersistentEntry('Change/Events/Http/Web/Rbs_Commerce', '\Rbs\Commerce\Events\Http\Web\Listeners');
        $config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Commerce', '\Rbs\Commerce\Events\Admin\Listeners');
        $config->addPersistentEntry('Change/Events/CollectionManager/Rbs_Commerce', '\Rbs\Commerce\Events\CollectionManager\Listeners');
        $config->addPersistentEntry('Change/Events/BlockManager/Rbs_Commerce', '\Rbs\Commerce\Events\BlockManager\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
