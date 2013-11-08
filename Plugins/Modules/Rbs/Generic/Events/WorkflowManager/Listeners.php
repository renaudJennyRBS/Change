<?php
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