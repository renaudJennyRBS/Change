<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Seo\Std;

/**
 * @name \Rbs\Seo\Std\ModelConfigurationGenerator
 */
class ModelConfigurationGenerator
{
	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onPluginSetupSuccess(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$modelManager = $applicationServices->getModelManager();

		$filters = [
			'publishable' => true,
			'abstract' => false,
			'inline' => false,
			'stateless' => false
		];
		$modelNames = $modelManager->getFilteredModelsNames($filters);

		if (count($modelNames))
		{
			$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Seo_ModelConfiguration');
			$qb = $dqb->dbQueryBuilder();
			$qb->addColumn($qb->getFragmentBuilder()->getDocumentColumn('modelName'));
			$query = $qb->query();
			$modelConfigurationNames = $query->getResults($query->getRowsConverter()->addStrCol('modelname'));

			$i18n = $applicationServices->getI18nManager();
			$tm = $applicationServices->getTransactionManager();
			foreach ($modelNames as $modelName)
			{
				$model = $modelManager->getModelByName($modelName);
				if (!in_array($model->getName(), $modelConfigurationNames))
				{
					/* @var $modelConfiguration \Rbs\Seo\Documents\ModelConfiguration */
					$modelConfiguration = $applicationServices->getDocumentManager()
						->getNewDocumentInstanceByModelName('Rbs_Seo_ModelConfiguration');
					$modelConfiguration->setLabel($i18n->trans($model->getLabelKey(), ['ucf']));
					$modelConfiguration->setModelName($model->getName());
					$modelConfiguration->setDocumentSeoAutoGenerate(true);
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