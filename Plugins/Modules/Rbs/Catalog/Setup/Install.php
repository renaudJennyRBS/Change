<?php
namespace Rbs\Catalog\Setup;

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
		$config->addPersistentEntry('Change/Events/Rbs/Admin/Rbs_Catalog', '\\Rbs\\Catalog\\Admin\\Register');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$rootNode = $documentServices->getTreeManager()->getRootNode('Rbs_Catalog');
		if (!$rootNode)
		{
			$transactionManager = $applicationServices->getTransactionManager();
			try
			{
				$transactionManager->begin();

				/* @var $folder \Rbs\Generic\Documents\Folder */
				$folder = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Generic_Folder');
				$folder->setLabel('Rbs_Catalog');
				$folder->create();
				$documentServices->getTreeManager()->insertRootNode($folder, 'Rbs_Catalog');

				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
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