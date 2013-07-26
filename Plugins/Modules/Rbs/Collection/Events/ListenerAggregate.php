<?php
namespace Rbs\Collection\Events;

use Change\Collection\CollectionManager;
use Rbs\Collection\Events\CollectionResolver;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Collection\Events\ListenerAggregate
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
		$callback = function (Event $event)
		{
			(new CollectionResolver())->getCollection($event);
		};
		$events->attach(CollectionManager::EVENT_GET_COLLECTION, $callback, 5);

		$callback = function (Event $event)
		{
			(new CollectionResolver())->getCodes($event);
		};
		$events->attach(CollectionManager::EVENT_GET_CODES, $callback, 5);
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