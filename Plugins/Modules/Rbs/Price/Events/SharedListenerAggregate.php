<?php
namespace Rbs\Price\Events;

use Zend\EventManager\SharedEventManagerInterface;

/**
 * @name \Rbs\Price\Events\SharedListenerAggregate
 */
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$this->registerCollections($events);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		// TODO: Implement detachShared() method.
	}

	/**
	 * @param SharedEventManagerInterface $events
	 */
	protected function registerCollections(SharedEventManagerInterface $events)
	{
		$callback = function (\Zend\EventManager\Event $event)
		{
			if ($event->getParam('code') === 'Rbs_Price_Collection_BillingAreasForShop')
			{
				$event->setParam('collection',
					new \Rbs\Price\Collection\BillingAreasForShop($event->getParam('documentServices'), $event->getParam('shopId')));
			}
			else if ($event->getParam('code') === 'Rbs_Price_Collection_Iso4217')
			{
				$event->setParam('collection', new \Rbs\Price\Collection\Iso4217Collection());
			}
		};
		$events->attach('CollectionManager', 'getCollection', $callback, 5);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$codes = $event->getParam('codes');
			$codes[] = 'Rbs_Price_Collection_BillingAreasForShop';
			$codes[] = 'Rbs_Price_Collection_Iso4217';
			$event->setParam('codes', $codes);
		};
		$events->attach('CollectionManager', 'getCodes', $callback, 1);
	}
}