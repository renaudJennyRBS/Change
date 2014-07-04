<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Job;

/**
 * @name \Rbs\Website\Job\GeneratePathRulesByModel
 */
class GeneratePathRulesByModel
{
	/**
	 * @param \Change\Job\Event $event
	 * @throws \Exception
	 */
	public function execute(\Change\Job\Event $event)
	{
		$modelName = $event->getJob()->getArgument('modelName');
		if (!$modelName) {
			$event->failed('invalid job arguments');
			return;
		}
		$applicationServices = $event->getApplicationServices();
		$model = $applicationServices->getModelManager()->getModelByName($modelName);
		if (!$model)
		{
			$event->failed('invalid job arguments');
		}

		if ($model->isPublishable())
		{
			$documentManager = $applicationServices->getDocumentManager();
			$transactionManager =  $applicationServices->getTransactionManager();

			$pathRuleBuilder = new \Rbs\Website\Events\PathRuleBuilder();
			$id = 0;
			while (true)
			{
				try
				{
					$transactionManager->begin();

					$q = $documentManager->getNewQuery($model);
					$q->andPredicates($q->gt('id', $id));
					$q->addOrder('id');
					$docs = $q->getDocuments(0, 50);

					$id = max($docs->ids());

					foreach ($docs as $doc)
					{
						try
						{
							$docEvent = new \Change\Documents\Events\Event('refreshPathRule', $doc, $event->getParams());
							$pathRuleBuilder->updatePathRules($docEvent);
						}
						catch (\Exception $e)
						{
							$event->getApplication()->getLogging()->error('Exception on refresh path rule for document:' . $doc);
							$event->getApplication()->getLogging()->exception($e);
						}
					}
					$transactionManager->commit();

					if ($docs->count() < 50)
					{
						break;
					}
				}
				catch (\Exception $e)
				{
					$transactionManager->rollBack($e);
					$event->failed($e->getMessage());
					return;
				}
			}
		}
		$event->success();
	}
}