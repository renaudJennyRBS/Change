<?php
namespace Rbs\User\Setup;

/**
 * @name \Rbs\User\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$presentationServices->getThemeManager()->installPluginTemplates($plugin);

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$groupModel = $documentServices->getModelManager()->getModelByName('Rbs_User_Group');

			$query = new \Change\Documents\Query\Query($documentServices, $groupModel);
			if ($query->andPredicates($query->eq('realm', 'Rbs_Admin'))->getCountDocuments() === 0)
			{
				/* @var $group \Rbs\User\Documents\Group */
				$group = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
				$group->setLabel('Backoffice');
				$group->setRealm('Rbs_Admin');
				$group->setIdentifier('backoffice');
				$group->create();
			}

			$query = new \Change\Documents\Query\Query($documentServices, $groupModel);
			if ($query->andPredicates($query->eq('realm', 'web'))->getCountDocuments() === 0)
			{
				/* @var $group2 \Rbs\User\Documents\Group */
				$group2 = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
				$group2->setLabel('Site Web');
				$group2->setRealm('web');
				$group2->setIdentifier('web');
				$group2->create();
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
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