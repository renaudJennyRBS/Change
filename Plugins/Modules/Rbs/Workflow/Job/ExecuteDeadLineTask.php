<?php
namespace Rbs\Workflow\Job;

use Change\Documents\DocumentServices;

/**
* @name \Rbs\Workflow\Job\ExecuteDeadLineTask
*/
class ExecuteDeadLineTask
{
	/**
	 * @param \Change\Job\Event $event
	 */
	public function execute($event)
	{
		$documentServices = $event->getDocumentServices();
		if ($documentServices instanceof DocumentServices)
		{
			$job = $event->getJob();
			$task = $documentServices->getDocumentManager()->getDocumentInstance($job->getArgument('taskId'));
			if ($task instanceof \Rbs\Workflow\Documents\Task &&
				$task->getStatus() === \Change\Workflow\Interfaces\WorkItem::STATUS_ENABLED)
			{
				$context = $job->getArguments();
				unset($context['taskId']);
				$context['jobId'] = $job->getId();
				$task->execute($context, -1);
			}

		}
	}
}