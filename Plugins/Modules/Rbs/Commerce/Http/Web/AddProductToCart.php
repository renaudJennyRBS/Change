<?php
namespace Rbs\Commerce\Http\Web;

use Change\Http\Web\Event;
use Rbs\Commerce\Services\CommerceServices;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Commerce\Http\Web\AddProductToCart
 */
class AddProductToCart extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			$this->add($commerceServices, $event);
			return;
		}
	}

	public function add(CommerceServices $commerceServices, Event $event)
	{
		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		if (isset($arguments['product']))
		{
			$dm = $event->getDocumentServices()->getDocumentManager();
			$product = $dm->getDocumentInstance($arguments['product']);
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
						if (isset($arguments['webStoreId']))
						{
							$webStore = $dm->getDocumentInstance(intval($arguments['webStoreId']), 'Rbs_Store_WebStore');
							unset($arguments['webStoreId']);
						}
						elseif (isset($arguments['options']['webStoreId']))
						{
							$webStore = $dm->getDocumentInstance(intval($arguments['options']['webStoreId']), 'Rbs_Store_WebStore');
							unset($arguments['options']['webStoreId']);
						}
						else
						{
							$webStore = $commerceServices->getWebStore();
						}

						if (!$webStore)
						{
							$e = new \RuntimeException('Web Store is not defined.', 999999);
							$e->httpStatusCode = HttpResponse::STATUS_CODE_409;
							throw $e;
						}

						$context['ownerId'] = $event->getAuthenticationManager()->getCurrentUser()->getId();

						$cart = $commerceServices->getCartManager()->getNewCart($webStore, null, null, $context);
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

					$result = $this->getNewAjaxResult(array('cart' => $cart->toArray(), 'lineKey' => $line->getKey()));
					$event->setResult($result);
				}
			}
		}
	}
}