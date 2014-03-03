<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Job;

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
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$job = $event->getJob();
			$task = $applicationServices->getDocumentManager()->getDocumentInstance($job->getArgument('taskId'));
			if ($task instanceof \Rbs\Workflow\Documents\Task
				&& $task->getStatus() === \Change\Workflow\Interfaces\WorkItem::STATUS_ENABLED
			)
			{
				$context = $job->getArguments();
				unset($context['taskId']);
				$context['jobId'] = $job->getId();
				$task->execute($context, -1);
			}
		}
	}
}