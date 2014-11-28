<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Website\Commands\RefreshPathRules
 */
class RefreshPathRules
{
	/**
	 * @param Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();
		$applicationServices = $event->getApplicationServices();

		$publishable = $event->getParam('publishable') == true;
		$specificModelName = $event->getParam('model');
		if (!is_string($specificModelName) || count(explode('_', $specificModelName)) != 3)
		{
			$specificModelName = null;
		}

		if (!$publishable && !$specificModelName)
		{
			$response->addCommentMessage('No model specified.');
			return;
		}

		$documentCount = 0;
		$pathRuleBuilder = new \Rbs\Website\Events\PathRuleBuilder();
		$tm = $applicationServices->getTransactionManager();
		$pathRuleManager =  $applicationServices->getPathRuleManager();
		$documentManager = $applicationServices->getDocumentManager();
		$modelManager = $applicationServices->getModelManager();
		$logging = $event->getApplication()->getLogging();

		foreach ($modelManager->getModelsNames() as $modelName)
		{
			$model = $modelManager->getModelByName($modelName);
			if (!$model || $model->isAbstract() || $model->isStateless() || !$model->isPublishable())
			{
				continue;
			}

			if ($specificModelName && $modelName != $specificModelName)
			{
				continue;
			}

			$response->addInfoMessage('Refresh ' . $modelName . ' model...');

			$id = 0;
			while (true)
			{
				try
				{
					$tm->begin();
					$applicationServices->getDocumentManager()->reset();
					$q = $applicationServices->getDocumentManager()->getNewQuery($model);
					$q->andPredicates($q->gt('id', $id));
					$q->addOrder('id');
					$docs = $q->getDocuments(0, 50);

					foreach ($docs as $doc)
					{
						$documentCount++;
						$pathRuleBuilder->refreshDocumentPathRules($doc, $pathRuleManager, $documentManager, $logging);
					}

					$tm->commit();
					if ($docs->count() < 50)
					{
						break;
					}
					else
					{
						$id = max($docs->ids());
					}
				}
				catch (\Exception $e)
				{
					throw $tm->rollBack($e);
				}
			}
		}

		$response->addInfoMessage($documentCount . ' documents are refreshed.');
	}
}