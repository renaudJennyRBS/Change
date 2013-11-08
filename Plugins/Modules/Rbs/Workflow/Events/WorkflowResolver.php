<?php
namespace Rbs\Workflow\Events;

/**
 * @name \Rbs\Workflow\Events\WorkflowResolver
 */
class WorkflowResolver
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function examine(\Change\Events\Event $event)
	{
		$startTask = $event->getParam('startTask');
		$date = $event->getParam('date');
		$applicationServices = $event->getApplicationServices();
		if ($startTask && $date && $applicationServices)
		{
			$workflowModel = $applicationServices->getModelManager()->getModelByName('Rbs_Workflow_Workflow');
			if ($workflowModel)
			{
				$dqb = $applicationServices->getDocumentManager()->getNewQuery($workflowModel);
				$workflow = $dqb->andPredicates($dqb->activated($date), $dqb->eq('startTask', $startTask))->getFirstDocument();
				if ($workflow)
				{
					$event->setParam('workflow', $workflow);
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function process(\Change\Events\Event $event)
	{
		$taskId = $event->getParam('taskId');
		$date = $event->getParam('date');
		$applicationServices = $event->getApplicationServices();
		if ($taskId && $date && $applicationServices)
		{
			$taskModel = $applicationServices->getModelManager()->getModelByName('Rbs_Workflow_Task');
			if ($taskModel)
			{
				$task = $applicationServices->getDocumentManager()->getDocumentInstance($taskId, $taskModel);
				if ($task instanceof \Rbs\Workflow\Documents\Task
					&& $task->getStatus() == \Change\Workflow\Interfaces\WorkItem::STATUS_ENABLED
				)
				{
					$workflowInstance = $task->getWorkflowInstance();
					if ($workflowInstance instanceof \Rbs\Workflow\Documents\WorkflowInstance
						&& $workflowInstance->getStatus() == \Change\Workflow\Interfaces\WorkflowInstance::STATUS_OPEN
					)
					{
						$event->setParam('taskId', $task->getTaskId());
						$event->setParam('workflowInstance', $workflowInstance);
					}
				}
			}
		}
	}
}