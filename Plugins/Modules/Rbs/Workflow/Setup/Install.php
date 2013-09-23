<?php
namespace Rbs\Workflow\Setup;

/**
 * @name \Rbs\Workflow\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $config
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $config)
	{
		$config->addPersistentEntry('Change/Events/Workflow/publicationProcess/Rbs_Workflow',
			'\\Rbs\\Workflow\\Tasks\\PublicationProcess\\Listeners');

		$config->addPersistentEntry('Change/Events/Workflow/correctionPublicationProcess/Rbs_Workflow',
			'\\Rbs\\Workflow\\Tasks\\CorrectionPublicationProcess\\Listeners');
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