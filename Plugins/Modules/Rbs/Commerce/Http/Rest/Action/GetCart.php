<?php
namespace Rbs\Commerce\Http\Rest\Action;

use Change\Http\Rest\Result\Link;

/**
* @name \Rbs\Commerce\Http\Rest\Action\GetCart
*/
class GetCart
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cartIdentifier = $event->getParam('cartIdentifier');
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if ($cart)
			{
				$result = new \Rbs\Commerce\Http\Rest\Result\CartResult();
				$result->setCart($cart->toArray());
				$link = new Link($event->getUrlManager(), 'commerce/cart/' . $cart->getIdentifier());
				$result->addLink($link);
				$event->setResult($result);
			}
		}
	}
}