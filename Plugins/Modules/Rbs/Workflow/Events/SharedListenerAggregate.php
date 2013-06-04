<?php
namespace Rbs\Workflow\Events;

use Rbs\Workflow\Events\WorkflowResolver;
use Change\Workflow\WorkflowManager;

/**
* @name \Rbs\Workflow\Events\SharedListenerAggregate
*/
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function attachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		$callback = function (\Zend\EventManager\Event $event)
		{
			$resolver = new WorkflowResolver();
			return $resolver->examine($event);
		};
		$events->attach(WorkflowManager::EVENT_MANAGER_IDENTIFIER, WorkflowManager::EVENT_EXAMINE, $callback, 5);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$resolver = new WorkflowResolver();
			return $resolver->process($event);
		};
		$events->attach(WorkflowManager::EVENT_MANAGER_IDENTIFIER, WorkflowManager::EVENT_PROCESS, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 *
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function detachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		//TODO
	}
}