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
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		if (isset($arguments['product']))
		{
			$product = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($arguments['product']);
			if ($product instanceof \Rbs\Commerce\Interfaces\CartLineConfigCapable)
			{
				$cartLineConfig = $product->getCartLineConfig($commerceServices, $arguments);
				if ($cartLineConfig && count($cartLineConfig->getItemConfigArray()))
				{
					$cartManager = $commerceServices->getCartManager();
					$cartIdentifier = $commerceServices->getCartIdentifier();
					$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
					if (!($cart instanceof \Rbs\Commerce\Interfaces\Cart))
					{
						$webStoreId = 0;
						if (isset($arguments['webStoreId']))
						{
							$webStoreId = intval($arguments['webStoreId']);
						}
						elseif (isset($arguments['options']['webStoreId']))
						{
							$webStoreId = intval($arguments['options']['webStoreId']);
						}

						if (!$webStoreId)
						{
							$e = new \RuntimeException('Web Store is not defined.', 999999);
							$e->httpStatusCode = HttpResponse::STATUS_CODE_409;
							throw $e;
						}

						$context = array('webStoreId' => $webStoreId);
						$context['ownerId'] = $event->getAuthenticationManager()->getCurrentUser()->getId();

						$cart = $commerceServices->getCartManager()->getNewCart(null, null, $context);

						$commerceServices->setCartIdentifier($cart->getIdentifier());
						$commerceServices->save();
					}

					$quantity = max(1, (isset($arguments['quantity']) ? intval($arguments['quantity']) : 1));
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