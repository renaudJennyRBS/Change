<?php
namespace Rbs\Simpleform\Setup;

/**
 * @name \Rbs\Simpleform\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{

	/**
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function attach($events, $plugin)
//	{
//		parent::attach($events, $plugin);
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
//	public function initialize($plugin)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		$images = $configuration->getEntry('Change/Storage/Rbs_Simpleform', array());
		$images = array_merge( array(
			'class' => '\\Change\\Storage\\Engines\\LocalStorage',
			'basePath' => 'App/Storage/Rbs_Simpleform/files',
			'useDBStat' => true,
			'baseURL' => "/index.php"
		), $images);
		$configuration->addPersistentEntry('Change/Storage/Rbs_Simpleform', $images);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
//	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
//	{
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
