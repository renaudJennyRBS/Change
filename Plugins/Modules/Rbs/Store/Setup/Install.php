<?php
namespace Rbs\Store\Setup;

/**
 * @name \Rbs\Store\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function initialize($plugin)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application)
	{
		/* @var $config \Change\Configuration\EditableConfiguration */
		$config = $application->getConfiguration();
		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Store', '\\Rbs\\Store\\Admin\\Register');
		$config->addPersistentEntry('Change/Events/CollectionManager/Rbs_Store',
			'\\Rbs\\Store\\Collection\\ListenerAggregate');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
//	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function finalize($plugin)
//	{
//	}
}
