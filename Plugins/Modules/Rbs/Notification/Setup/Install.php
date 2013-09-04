<?php
namespace Rbs\Notification\Setup;

/**
 * @name \Rbs\Notification\Setup\Install
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
//	public function executeApplication($plugin, $application)
//	{
//		/* @var $config \Change\Configuration\EditableConfiguration */
//		$config = $application->getConfiguration();
//	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$jm = new \Change\Job\JobManager();
		$jm->setApplicationServices($applicationServices);
		$jm->createNewJob('Rbs_Notification_SendMails');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
