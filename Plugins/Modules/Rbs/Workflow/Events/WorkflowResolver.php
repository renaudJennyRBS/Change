<?php
namespace Rbs\Workflow\Events;

/**
 * @name \Rbs\Workflow\Events\WorkflowResolver
 */
class WorkflowResolver
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function examine(\Zend\EventManager\Event $event)
	{
		$startTask = $event->getParam('startTask');
		$date = $event->getParam('date');
		$documentServices = $event->getParam('documentServices');
		if ($startTask && $date && $documentServices instanceof \Change\Documents\DocumentServices)
		{
			$workflowModel = $documentServices->getModelManager()->getModelByName('Rbs_Workflow_Workflow');
			if ($workflowModel)
			{
				$dqb = new \Change\Documents\Query\Query($documentServices, $workflowModel);
				$workflow = $dqb->andPredicates($dqb->activated($date), $dqb->eq('startTask', $startTask))->getFirstDocument();
				if ($workflow)
				{
					$event->setParam('workflow', $workflow);
				}
			}
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function process(\Zend\EventManager\Event $event)
	{
		$taskId = $event->getParam('taskId');
		$date = $event->getParam('date');
		$documentServices = $event->getParam('documentServices');
		if ($taskId && $date && $documentServices instanceof \Change\Documents\DocumentServices)
		{
			$taskModel = $documentServices->getModelManager()->getModelByName('Rbs_Workflow_Task');
			if ($taskModel)
			{
				$task = $documentServices->getDocumentManager()->getDocumentInstance($taskId, $taskModel);
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