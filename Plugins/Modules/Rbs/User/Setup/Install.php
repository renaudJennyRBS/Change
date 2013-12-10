<?php
namespace Rbs\User\Setup;

/**
 * @name \Rbs\User\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$applicationServices->getThemeManager()->installPluginTemplates($plugin);

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$groupModel = $applicationServices->getModelManager()->getModelByName('Rbs_User_Group');

			$query = $applicationServices->getDocumentManager()->getNewQuery($groupModel);
			if ($query->andPredicates($query->eq('realm', 'Rbs_Admin'))->getCountDocuments() === 0)
			{
				/* @var $group \Rbs\User\Documents\Group */
				$group = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
				$group->setLabel('Backoffice');
				$group->setRealm('Rbs_Admin');
				$group->setIdentifier('backoffice');
				$group->create();
			}

			$query = $applicationServices->getDocumentManager()->getNewQuery($groupModel);
			if ($query->andPredicates($query->eq('realm', 'web'))->getCountDocuments() === 0)
			{
				/* @var $group2 \Rbs\User\Documents\Group */
				$group2 = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($groupModel);
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

		$jobManager = $applicationServices->getJobManager();
		$jobManager->createNewJob('Rbs_User_CleanAccountRequestTable');
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}