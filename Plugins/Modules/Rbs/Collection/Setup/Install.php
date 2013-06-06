<?php
namespace Rbs\Collection\Setup;

/**
 * @name \Rbs\Collection\Setup\Install
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

		$config->addPersistentEntry('Rbs/Admin/Listeners/Rbs_Collection',
			'\\Rbs\\Collection\\Admin\\Register');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
//	public function executeServices($plugin, $documentServices, $presentationServices)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function finalize($plugin)
//	{
//	}
}
