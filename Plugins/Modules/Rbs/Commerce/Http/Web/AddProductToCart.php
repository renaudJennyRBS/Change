<?php
namespace Rbs\Commerce\Http\Web;

use Change\Http\Web\Event;
use Rbs\Commerce\Services\CommerceServices;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Commerce\Http\Web\AddProductToCart
 */
class AddProductToCart
{
	/**
	 * @param Event $event
	 */
	public static function executeByName(Event $event)
	{
		$commerceServices = $event->getParam('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			(new self())->add($commerceServices, $event);
			return;
		}
		throw new \RuntimeException('Unable to get CommerceServices', 999999);
	}

	public function add(CommerceServices $commerceServices, Event $event)
	{
		$request = $event->getRequest();
		$args = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		if (isset($args['product']))
		{
			$product = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($args['product']);
			if ($product instanceof \Rbs\Commerce\Interfaces\CartLineConfigCapable)
			{
				$cartLineConfig = $product->getCartLineConfig($commerceServices, $args);
				$quantity = max(1.0, isset($args['quantity']) ? floatval($args['quantity']) : 1.0);

				$cartManager = $commerceServices->getCartManager();
				$cartIdentifier = $commerceServices->getCartIdentifier();
				$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
				if (!($cart instanceof \Rbs\Commerce\Interfaces\Cart))
				{
					$cart = $commerceServices->getCartManager()->getNewCart();
					$commerceServices->setCartIdentifier($cart->getIdentifier());
					$commerceServices->save();
				}

				$line = $cartManager->getLineByKey($cart, $cartLineConfig->getKey());
				if ($line)
				{
					$cartManager->updateLineQuantityByKey($cart, $cartLineConfig->getKey(), $line->getQuantity() + $quantity);
				}
				else
				{
					$line = $cartManager->addLine($cart, $cartLineConfig, $quantity);
				}
				$cartManager->saveCart($cart);

				$result = new \Change\Http\Web\Result\AjaxResult(array('cart' => $cart->toArray(), 'lineKey' => $line->getKey()));
				$event->setResult($result);
			}
		}
	}
}