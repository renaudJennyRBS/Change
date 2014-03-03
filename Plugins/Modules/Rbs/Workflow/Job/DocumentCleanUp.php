<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Job;

use Change\Workflow\Interfaces\WorkflowInstance;
use Change\Workflow\Interfaces\WorkItem;
use Rbs\Workflow\Documents\Task;

/**
 * @name \Rbs\Workflow\Job\DocumentCleanUp
 */
class DocumentCleanUp
{
	/**
	 * @param \Change\Job\Event $event
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

		$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Workflow_Task');
		$query->andPredicates(
			$query->in('status', array(WorkItem::STATUS_ENABLED, WorkItem::STATUS_IN_PROGRESS)),
			$query->eq('document', $documentId)
		);

		$query->addOrder('workflowInstance');

		$tasks = $query->getDocuments();

		/* @var $task Task */
		foreach ($tasks as $task)
		{
			try
			{
				$this->cancelTask($task, $applicationServices);
			}
			catch (\Exception $e)
			{
				$applicationServices->getLogging()->exception($e);
			}
		}

		$event->success();
	}

	/**
	 * @param \Change\Job\Event $event
	 */
	public function localizedCleanUp(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$documentId = $job->getArgument('id');
		$modelName = $job->getArgument('model');
		$LCID = $job->getArgument('LCID');

		if (!is_numeric($documentId) || !is_string($modelName)|| !is_string($LCID))
		{
			$event->failed('Invalid Arguments ' . $documentId . ', ' . $modelName . ', ' . $LCID);
			return;
		}

		$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Workflow_Task');
		$query->andPredicates(
			$query->in('status', array(WorkItem::STATUS_ENABLED, WorkItem::STATUS_IN_PROGRESS)),
			$query->eq('document', $documentId),
			$query->eq('documentLCID', $LCID)
		);
		$query->addOrder('workflowInstance');

		$tasks = $query->getDocuments();

		/* @var $task Task */
		foreach ($tasks as $task)
		{
			try
			{
				$this->cancelTask($task, $applicationServices);
			}
			catch (\Exception $e)
			{
				$applicationServices->getLogging()->exception($e);
			}
		}
		$event->success();
	}

	/**
	 * @param \Change\Job\Event $event
	 */
	public function onCorrectionFiled($event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$documentId = $job->getArgument('documentId');
		$LCID = $job->getArgument('LCID');
		$correctionId = $job->getArgument('correctionId');

		$document = $applicationServices->getDocumentManager()->getDocumentInstance($documentId);
		if (!$document)
		{
			$event->success();
			return;
		}

		$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Workflow_Task');
		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			if ($LCID === \Change\Documents\Correction::NULL_LCID_KEY)
			{
				$LCID = $document->getRefLCID();
			}
			$query->andPredicates(
				$query->in('status', array(WorkItem::STATUS_ENABLED, WorkItem::STATUS_IN_PROGRESS)),
				$query->eq('document', $documentId),
				$query->eq('documentLCID', $LCID)
			);
		}
		else
		{
			$query->andPredicates(
				$query->in('status', array(WorkItem::STATUS_ENABLED, WorkItem::STATUS_IN_PROGRESS)),
				$query->eq('document', $documentId)
			);
		}
		$query->addOrder('workflowInstance');
		$tasks = $query->getDocuments();

		/* @var $task Task */
		foreach ($tasks as $task)
		{
			try
			{
				$workflowInstance = $task->getWorkflowInstance();
				if (!$workflowInstance)
				{
					$this->cancelTask($task, $applicationServices);
				}
				else
				{
					$context = $workflowInstance->getContext();
					if (isset($context[WorkItem::CORRECTION_ID_CONTEXT_KEY]) && $context[WorkItem::CORRECTION_ID_CONTEXT_KEY] == $correctionId)
					{
						$this->cancelTask($task, $applicationServices);
					}
				}
			}
			catch (\Exception $e)
			{
				$applicationServices->getLogging()->exception($e);
			}
		}

		$event->success();
	}

	/**
	 * @param Task $task
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	protected function cancelTask(Task $task, $applicationServices)
	{
		try
		{
			$applicationServices->getTransactionManager()->begin();
			$workflowInstance = $task->getWorkflowInstance();
			if ($workflowInstance && in_array($workflowInstance->getStatus(), [WorkflowInstance::STATUS_OPEN, WorkflowInstance::STATUS_SUSPENDED])
			)
			{
				$workflowInstance->cancel(new \DateTime());
				$workflowInstance->update();
			}
			$task->setStatus(WorkItem::STATUS_CANCELLED);
			$task->update();

			$applicationServices->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			throw $applicationServices->getTransactionManager()->rollBack($e);
		}
	}
}