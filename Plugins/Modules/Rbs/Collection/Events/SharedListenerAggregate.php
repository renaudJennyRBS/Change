<?php
namespace Rbs\Collection\Events;

use Rbs\Collection\Events\CollectionResolver;
use Change\Collection\CollectionManager;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;

/**
* @name \Rbs\Collection\Events\SharedListenerAggregate
*/
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			$resolver = new CollectionResolver();
			return $resolver->getCollection($event);
		};
		$events->attach(CollectionManager::EVENT_MANAGER_IDENTIFIER, CollectionManager::EVENT_GET_COLLECTION, $callback, 5);

		$callback = function (Event $event)
		{
			$resolver = new CollectionResolver();
			return $resolver->getCodes($event);
		};
		$events->attach(CollectionManager::EVENT_MANAGER_IDENTIFIER, CollectionManager::EVENT_GET_CODES, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 *
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		//TODO
	}
}