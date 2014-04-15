<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Job;

/**
 * @name \Rbs\Catalog\Job\CleanUpAttribute
 */
class CleanUpAttribute
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$modelName = $job->getArgument('model');
		$model = $applicationServices->getModelManager()->getModelByName($modelName);
		if ($model && ($model->getName() == 'Rbs_Catalog_Attribute' || in_array('Rbs_Catalog_Attribute', $model->getAncestorsNames())))
		{
			$dbProvider = $applicationServices->getDbProvider();
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();

				$dqb = $dbProvider->getNewStatementBuilder();
				$fb = $dqb->getFragmentBuilder();
				$dqb->delete($fb->table('rbs_catalog_dat_attribute'))
					->where($fb->eq($fb->column('attribute_id'), $fb->number($job->getArgument('id'))));

				$dqb->deleteQuery()->execute();

				$tm->commit();
			}
			catch (\Exception $e)
			{
				$tm->rollBack($e);
			}
		}
	}
}