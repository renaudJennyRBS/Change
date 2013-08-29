<?php
namespace Rbs\Commerce\Http\Rest\Action;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Commerce\Http\Rest\Action\UpdateCart
 */
class UpdateCart
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$commerceServices = $event->getParam('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\Services\CommerceServices)
		{
			$cartIdentifier = $event->getParam('cartIdentifier');
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if ($cart)
			{




				(new GetCart())->execute($event);
			}
		}
	}
}