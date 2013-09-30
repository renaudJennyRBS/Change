<?php
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
		$documentServices = $event->getDocumentServices();
		$documentId = $job->getArgument('id');
		$modelName = $job->getArgument('model');
		if (!is_numeric($documentId) || !is_string($modelName))
		{
			$event->failed('Invalid Arguments ' . $documentId . ', ' . $modelName);
			return;
		}

		$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Workflow_Task');
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
				$this->cancelTask($task);
			}
			catch (\Exception $e)
			{
				$documentServices->getApplicationServices()->getLogging()->exception($e);
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
		$documentServices = $event->getDocumentServices();
		$documentId = $job->getArgument('id');
		$modelName = $job->getArgument('model');
		$LCID = $job->getArgument('LCID');

		if (!is_numeric($documentId) || !is_string($modelName)|| !is_string($LCID))
		{
			$event->failed('Invalid Arguments ' . $documentId . ', ' . $modelName . ', ' . $LCID);
			return;
		}

		$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Workflow_Task');
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
				$this->cancelTask($task);
			}
			catch (\Exception $e)
			{
				$documentServices->getApplicationServices()->getLogging()->exception($e);
			}
		}
		$event->success();
	}

	/**
	 * @param Task $task
	 * @throws \Exception
	 */
	protected function cancelTask(Task $task)
	{
		$applicationServices = $task->getDocumentServices()->getApplicationServices();
		try
		{
			$applicationServices->getTransactionManager()->begin();

			$workflowInstance = $task->getWorkflowInstance();
			if ($workflowInstance
				&& in_array($workflowInstance->getStatus(),
					array(WorkflowInstance::STATUS_OPEN, WorkflowInstance::STATUS_SUSPENDED))
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