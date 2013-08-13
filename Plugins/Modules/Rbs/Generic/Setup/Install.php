<?php
namespace Rbs\Generic\Setup;

/**
 * @name \Rbs\Generic\Setup\Install
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

		$config->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Generic', '\Rbs\Generic\Events\SharedListeners');
		$config->addPersistentEntry('Change/Events/CollectionManager/Rbs_Generic',
			'\Rbs\Generic\Events\CollectionManager\Listeners');

		$config->addPersistentEntry('Change/Events/Http/Rest/Rbs_Generic', '\Rbs\Generic\Events\Http\Rest\Listeners');
		$config->addPersistentEntry('Change/Events/Http/Web/Rbs_Generic', '\Rbs\Generic\Events\Http\Web\Listeners');

		$config->addPersistentEntry('Change/Events/Commands/Rbs_Generic', '\Rbs\Generic\Events\Commands\Listeners');
		$config->addPersistentEntry('Change/Events/Db/Rbs_Generic', '\Rbs\Generic\Events\Db\Listeners');
		$config->addPersistentEntry('Change/Events/WorkflowManager/Rbs_Generic', '\Rbs\Generic\Events\WorkflowManager\Listeners');

		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Generic', '\Rbs\Generic\Events\Admin\Listeners');
		$config->addPersistentEntry('Change/Events/BlockManager/Rbs_Generic', '\Rbs\Generic\Events\BlockManager\Listeners');

		$config->addPersistentEntry('Change/Events/ProfileManager/Rbs_Generic', '\Rbs\Generic\Events\ProfileManager\Listeners');
		$config->addPersistentEntry('Change/Events/AuthenticationManager/Rbs_Generic',
			'\Rbs\Generic\Events\AuthenticationManager\Listeners');

		$config->addPersistentEntry('Change/Events/ThemeManager/Rbs_Generic', '\Rbs\Generic\Events\ThemeManager\Listeners');
		$config->addPersistentEntry('Change/Events/RichTextManager/Rbs_Generic', '\Rbs\Generic\Events\RichTextManager\Listeners');
		$config->addPersistentEntry('Change/Events/JobManager/Rbs_Generic', '\Rbs\Generic\Events\JobManager\Listeners');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}