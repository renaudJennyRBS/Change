<?php
namespace Rbs\Store\Collection;

use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Store\Collection\ListenerAggregate
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
				case 'Rbs_Store_Collection_WebStores':
					(new Collections())->addWebStores($event);
					break;
			}
		};
		$events->attach('getCollection', $callback, 5);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$codes = $event->getParam('codes');
			$codes[] = 'Rbs_Store_Collection_WebStores';
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