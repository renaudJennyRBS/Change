<?php
namespace Rbs\Price\Collection;

use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Price\Collection\ListenerAggregate
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
			$collection = null;
			switch ($event->getParam('code'))
			{
				case 'Rbs_Price_Collection_BillingAreasForWebStore':
					$collection = new \Rbs\Price\Collection\BillingAreasForWebStoreCollection($event->getParam('documentServices'), $event->getParam('webStoreId'));
					break;
				case 'Rbs_Price_Collection_Iso4217':
					$collection = new \Rbs\Price\Collection\Iso4217Collection();
					break;
				case 'Rbs_Price_Collection_TaxRoundingStrategy':
					$collection = new \Rbs\Price\Collection\TaxRoundingStrategyCollection($event->getParam('documentServices'));
					break;
				default:
					break;
			}
			if ($collection)
			{
				$event->setParam('collection', $collection);
				$event->stopPropagation();
			}
		};
		$events->attach('getCollection', $callback, 10);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$codes = $event->getParam('codes', array());
			$codes[] = 'Rbs_Price_Collection_BillingAreasForWebStore';
			$codes[] = 'Rbs_Price_Collection_Iso4217';
			$codes[] = 'Rbs_Price_Collection_TaxRoundingStrategy';
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