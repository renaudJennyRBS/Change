<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Job;

/**
 * @name \Rbs\Website\Job\DocumentCleanUp
 */
class DocumentCleanUp
{
	/**
	 * @param \Change\Job\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function cleanUp(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$documentId = $job->getArgument('id');
		$modelName = $job->getArgument('model');
		if (!is_numeric($documentId) || !is_string($modelName))
		{
			$event->failed('Invalid Arguments ' . $documentId . ', ' . $modelName);
			return;
		}

		if (in_array($modelName, ['Rbs_Website_StaticPage', 'Rbs_Website_StaticPage']))
		{
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Website_SectionPageFunction');
			$query->andPredicates($query->eq('page', $documentId));
			$sectionPageFunctions = $query->getDocuments();
			$event->getApplication()->getLogging()->info('Delete ' . $sectionPageFunctions->count() . ' SectionPageFunctions.');
			if ($sectionPageFunctions->count())
			{
				$transactionManager = $applicationServices->getTransactionManager();
				try
				{
					$transactionManager->begin();
					foreach ($query->getDocuments() as $sectionPageFunction)
					{
						$sectionPageFunction->delete();
					}
					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
		}
	}
}