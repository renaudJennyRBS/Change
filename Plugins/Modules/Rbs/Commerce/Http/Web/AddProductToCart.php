<?php
namespace Rbs\Commerce\Http\Web;

use Change\Http\Web\Event;
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
		if ($event->getRequest()->getMethod() === 'POST')
		{
			(new self())->add($event);
		}
	}

	public function add(Event $event)
	{
		$args = $event->getRequest()->getPost()->toArray();
		if (isset($args['product']))
		{
			$product = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($args['product']);
			if ($product instanceof \Rbs\Commerce\Interfaces\CartLineConfigCapable)
			{
				$cartLineConfig = $product->getCartLineConfig();
				$options = isset($args['options']) ? $args['options'] : array();
				if (is_array($options))
				{
					foreach ($options as $optName => $optValue)
					{
						$cartLineConfig->setOption($optName, $optValue);
					}
				}

				$quantity = max(1.0, isset($args['quantity']) ? floatval($args['quantity']) : 1.0);
				$commerceServices = $event->getParam('commerceServices');
				if ($commerceServices instanceof \Rbs\Commerce\Services\CommerceServices)
				{
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
}