<?php
namespace Rbs\Event\Events\CollectionManager;

use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Collection\CollectionManager;

/**
 * @name \Rbs\Event\Events\CollectionManager\Listeners
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
		$callback = function (Event $event)
		{
			switch ($event->getParam('code'))
			{
				case 'Rbs_Event_Collection_SectionRestrictions':
					(new \Rbs\Event\Collection\Collections())->sectionRestrictions($event);
					break;
			}
		};
		$events->attach(CollectionManager::EVENT_GET_COLLECTION, $callback, 10);

		$callback = function (Event $event)
		{
			$codes = $event->getParam('codes', array());
			$codes[] = 'Rbs_Event_Collection_SectionRestrictions';
			$event->setParam('codes', $codes);
		};
		$events->attach(CollectionManager::EVENT_GET_CODES, $callback, 1);
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