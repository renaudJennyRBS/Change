<?php
namespace Rbs\Commerce\Events\CrossSellingManager;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\CartManager\Listeners
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
			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$event->setParam('csProducts', (new \Rbs\Catalog\Std\CrossSellingEngine($cs))->getCrossSellingProductsByProduct($event));
			}
		};
		$events->attach('getCrossSellingForProduct', $callback, 5);

		$callback = function (Event $event)
		{
			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$event->setParam('csProducts', (new \Rbs\Catalog\Std\CrossSellingEngine($cs))->getCrossSellingProductsByCart($event));
			}
		};
		$events->attach('getCrossSellingForCart', $callback, 5);
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