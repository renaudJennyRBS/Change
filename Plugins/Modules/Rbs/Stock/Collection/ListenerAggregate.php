<?php
namespace Rbs\Stock\Collection;

use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Stock\Collection\ListenerAggregate
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
				case 'Rbs_Stock_Collection_Unit':
					(new Collections())->addUnit($event);
					break;
			}
		};
		$events->attach('getCollection', $callback, 10);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$codes = $event->getParam('codes', array());
			$codes[] = 'Rbs_Stock_Collection_Unit';
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