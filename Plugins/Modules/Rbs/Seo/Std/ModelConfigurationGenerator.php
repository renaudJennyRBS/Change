<?php
namespace Rbs\Seo\Std;

/**
 * @name \Rbs\Seo\Std\ModelConfigurationGenerator
 */
class ModelConfigurationGenerator
{
	/**
	 * @param \Zend\EventManager\Event $event
	 * @throws \Exception
	 */
	public function onPluginSetupSuccess(\Zend\EventManager\Event $event)
	{
		$application = $event->getParam('application');
		/* @var $application \Change\Application */
		$applicationServices = new \Change\Application\ApplicationServices($application);
		$modelManager = new \Change\Documents\ModelManager();
		$modelManager->setApplicationServices($applicationServices);

		$publishableModels = [];
		$publishableModelNames = [];
		foreach ($modelManager->getModelsNames() as $modelName)
		{
			$model = $modelManager->getModelByName($modelName);
			if ($model->isPublishable())
			{
				$publishableModels[] = $model;
				$publishableModelNames[] = $model->getName();
			}
		}

		if (count($publishableModels))
		{
			$documentServices = new \Change\Documents\DocumentServices($applicationServices);
			$dqb = new \Change\Documents\Query\Query($documentServices, 'Rbs_Seo_ModelConfiguration');
			$qb = $dqb->dbQueryBuilder();
			$qb->addColumn($qb->getFragmentBuilder()->getDocumentColumn('modelName'));
			$query = $qb->query();
			$modelConfigurationNames = $query->getResults($query->getRowsConverter()->addStrCol('modelname'));

			$i18n = $applicationServices->getI18nManager();
			$tm = $applicationServices->getTransactionManager();
			foreach ($publishableModels as $model)
			{
				/* @var $model \Change\Documents\AbstractModel */
				if (!in_array($model->getName(), $modelConfigurationNames))
				{
					$modelConfiguration = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_ModelConfiguration');
					/* @var $modelConfiguration \Rbs\Seo\Documents\ModelConfiguration */
					$modelConfiguration->setLabel($i18n->trans($model->getLabelKey(), array('ucf')));
					$modelConfiguration->setModelName($model->getName());
					$modelConfiguration->setDocumentSeoAutoGenerate(false);
					$modelConfiguration->setSitemapDefaultChangeFrequency('daily');
					$modelConfiguration->setSitemapDefaultPriority(0.5);
					try
					{
						$tm->begin();
						$modelConfiguration->save();
						$tm->commit();
					}
					catch (\Exception $e)
					{
						throw $tm->rollBack($e);
					}
				}
			}
		}
	}
}