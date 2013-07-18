<?php
namespace Rbs\Generic\Collection;

use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Generic\Collection\ListenerAggregate
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
			switch ($event->getParam('code'))
			{
				case 'Rbs_Generic_Collection_SortDirections':
					(new Collections())->addSortDirections($event);
					break;
			}
		};
		$events->attach('getCollection', $callback, 5);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$codes = $event->getParam('codes');
			$codes[] = 'Rbs_Generic_Collection_SortDirections';
			$event->setParam('codes', $codes);
		};
		$events->attach('getCodes', $callback, 1);
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