<?php
namespace Rbs\Seo\Std;

/**
 * @name \Rbs\Seo\Std\DocumentSeoGenerator
 */
class DocumentSeoGenerator
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
			$dqb = new \Change\Documents\Query\Query($documentServices, 'Rbs_Seo_SitemapModel');
			$qb = $dqb->dbQueryBuilder();
			$qb->addColumn($qb->getFragmentBuilder()->getDocumentColumn('modelName'));
			$query = $qb->query();
			$sitemapModelCodes = $query->getResults($query->getRowsConverter()->addStrCol('modelname'));

			$i18n = $applicationServices->getI18nManager();
			$tm = $applicationServices->getTransactionManager();
			foreach ($publishableModels as $model)
			{
				/* @var $model \Change\Documents\AbstractModel */
				if (!in_array($model->getName(), $sitemapModelCodes))
				{
					$sitemapModel = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_SitemapModel');
					/* @var $sitemapModel \Rbs\Seo\Documents\SitemapModel */
					$sitemapModel->setLabel($i18n->trans($model->getLabelKey(), array('ucf')));
					$sitemapModel->setModelName($model->getName());
					$sitemapModel->setDocumentSeoAutoGenerate(false);
					$sitemapModel->setDefaultChangeFrequency('daily');
					$sitemapModel->setDefaultPriority(0.5);
					try
					{
						$tm->begin();
						$sitemapModel->save();
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