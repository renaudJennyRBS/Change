<?php
namespace Rbs\Workflow\Setup;

/**
 * @name \Rbs\Workflow\Setup\Install
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

		$config->addPersistentEntry('Change/Events/WorkflowManager/Rbs_Workflow',
			'\\Rbs\\Workflow\\Events\\ListenerAggregate');

		$config->addPersistentEntry('Change/Events/Workflow/publicationProcess/Rbs_Workflow',
			'\\Rbs\\Workflow\\Tasks\\PublicationProcess\\ListenerAggregate');

		$config->addPersistentEntry('Change/Events/Workflow/correctionPublicationProcess/Rbs_Workflow',
			'\\Rbs\\Workflow\\Tasks\\CorrectionPublicationProcess\\ListenerAggregate');

		$config->addPersistentEntry('Change/Events/ListenerAggregateClasses/Rbs_Workflow',
			'\\Rbs\\Workflow\\Events\\SharedListenerAggregate');
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
		$workflowManager = new \Change\Workflow\WorkflowManager();
		$workflowManager->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
		$workflowManager->setDocumentServices($documentServices);

		if ($workflowManager->getWorkflow('publicationProcess') === null)
		{
			try
			{
				$applicationServices->getTransactionManager()->begin();
				$publicationProcessWorkflow = new PublicationProcessWorkflow($documentServices);
				$workflow = $publicationProcessWorkflow->install();
				$plugin->setConfigurationEntry('publicationProcess', $workflow->getId());
				$applicationServices->getTransactionManager()->commit();
			}
			catch (\Exception $e)
			{
				throw $applicationServices->getTransactionManager()->rollBack( $e);
			}
		}

		if ($workflowManager->getWorkflow('correctionPublicationProcess') === null)
		{
			try
			{
				$applicationServices->getTransactionManager()->begin();
				$publicationProcessWorkflow = new CorrectionPublicationProcessWorkflow($documentServices);
				$workflow = $publicationProcessWorkflow->install();
				$plugin->setConfigurationEntry('correctionPublicationProcess', $workflow->getId());
				$applicationServices->getTransactionManager()->commit();
			}
			catch (\Exception $e)
			{
				throw $applicationServices->getTransactionManager()->rollBack( $e);
			}
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}