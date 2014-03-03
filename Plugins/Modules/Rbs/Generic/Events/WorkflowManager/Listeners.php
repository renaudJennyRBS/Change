<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\WorkflowManager;

use Change\Workflow\WorkflowManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\WorkflowManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (\Change\Events\Event $event)
		{
			$resolver = new \Rbs\Workflow\Events\WorkflowResolver();
			$resolver->examine($event);
		};
		$events->attach(WorkflowManager::EVENT_EXAMINE, $callback, 5);

		$callback = function (\Change\Events\Event $event)
		{
			$resolver = new \Rbs\Workflow\Events\WorkflowResolver();
			$resolver->process($event);
		};
		$events->attach(WorkflowManager::EVENT_PROCESS, $callback, 5);

		$callback = function (\Change\Events\Event $event)
		{
			(new \Rbs\Workflow\Http\Rest\Actions\ExecuteTask())->executeAll($event);
		};
		$events->attach(WorkflowManager::EVENT_EXECUTE_ALL, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}