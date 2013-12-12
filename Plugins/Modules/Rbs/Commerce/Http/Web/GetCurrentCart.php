<?php
namespace Rbs\Commerce\Http\Web;

use Rbs\Commerce\Cart\Cart;
use Rbs\Commerce\CommerceServices;

/**
* @name \Rbs\Commerce\Http\Web\GetCurrentCart
*/
class GetCurrentCart extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			$cartManager = $commerceServices->getCartManager();
			$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
			$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
			if (!$cart)
			{
				$cart = new Cart(null, $cartManager);
			}

			$result = $this->getNewAjaxResult($cart->toArray());
			$event->setResult($result);
			return;
		}
	}
}