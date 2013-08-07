<?php
namespace Rbs\Stock\Setup;

/**
 * @name \Rbs\Stock\Setup\Install
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
		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Stock', '\\Rbs\\Stock\\Admin\\Register');
		$config->addPersistentEntry('Change/Events/CollectionManager/Rbs_Stock', '\\Rbs\\Stock\\Collection\\ListenerAggregate');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$schema = new Schema($applicationServices->getDbProvider()->getSchemaManager());
		$schema->generate();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
