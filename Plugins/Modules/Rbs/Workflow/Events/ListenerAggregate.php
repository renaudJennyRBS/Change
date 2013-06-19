<?php
namespace Rbs\Workflow\Events;

use Change\Workflow\WorkflowManager;
use Rbs\Workflow\Events\WorkflowResolver;
use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Workflow\Events\ListenerAggregate
 */
class ListenerAggregate implements \Zend\EventManager\ListenerAggregateInterface
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
		$callback = function (\Zend\EventManager\Event $event)
		{
			$resolver = new WorkflowResolver();
			$resolver->examine($event);
		};
		$events->attach(WorkflowManager::EVENT_EXAMINE, $callback, 5);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$resolver = new WorkflowResolver();
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