<?php
namespace Rbs\Commerce\Events\CartManager;

use Zend\EventManager\Event;
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
			$cs = $event->getParam('commerceServices');
			if ($cs instanceof \Rbs\Commerce\Services\CommerceServices)
			{
				$billingArea = $event->getParam('billingArea', null);
				$zone =  $event->getParam('zone', null);
				$context =  $event->getParam('context', array());
				$event->setParam('cart', (new \Rbs\Commerce\Cart\CartStorage())->getNewCart($cs, $billingArea, $zone, $context));
			}
		};
		$events->attach('getNewCart', $callback, 5);

		$callback = function (Event $event)
		{
			$cs = $event->getParam('commerceServices');
			if ($cs instanceof \Rbs\Commerce\Services\CommerceServices)
			{
				$cart = (new \Rbs\Commerce\Cart\CartStorage())->loadCart($event->getParam('cartIdentifier'), $cs);
				if ($cart)
				{
					$event->setParam('cart', $cart);
				}
			}
		};
		$events->attach('getCartByIdentifier', $callback, 5);

		$callback = function (Event $event)
		{
			$cart = $event->getParam('cart');
			if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cart->getCommerceServices())
			{
				(new \Rbs\Commerce\Cart\CartStorage())->saveCart($cart);
			}
		};
		$events->attach('saveCart', $callback, 5);


		$callback = function (Event $event)
		{
			$cart = $event->getParam('cart');
			$ownerId =  $event->getParam('ownerId', null);
			if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cart->getCommerceServices())
			{
				(new \Rbs\Commerce\Cart\CartStorage())->lockCart($cart, $ownerId);
			}
		};
		$events->attach('lockCart', $callback, 5);
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