<?php
namespace Rbs\Commerce\Http\Web;

use Change\Http\Web\Event;
use Rbs\Commerce\Services\CommerceServices;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Commerce\Http\Web\UpdateCartLine
 */
class UpdateCartLine
{
	/**
	 * @param Event $event
	 */
	public static function executeByName(Event $event)
	{
		$commerceServices = $event->getParam('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			$request = $event->getRequest();
			if ($request->isDelete() || $request->getPost('delete', $request->getQuery('delete')))
			{
				(new self())->delete($commerceServices, $event);
				return;
			}
			else
			{
				(new self())->update($commerceServices, $event);
				return;
			}
		}
		throw new \RuntimeException('Unable to get CommerceServices', 999999);
	}

	/**
	 * @param CommerceServices $commerceServices
	 * @return null|\Rbs\Commerce\Interfaces\Cart
	 */
	protected function getCart(CommerceServices $commerceServices)
	{
		$cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceServices->getCartIdentifier();
		return ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
	}

	public function delete(CommerceServices $commerceServices, Event $event)
	{
		$request = $event->getRequest();
		$lineKey =  $request->getPost('lineKey', $request->getQuery('lineKey'));
		if ($lineKey)
		{
			$cart = $this->getCart($commerceServices);
			if ($cart)
			{
				$cartLine = $cart->getLineByKey($lineKey);
				if ($cartLine)
				{
					$cartManager = $commerceServices->getCartManager();
					$cart->removeLineByKey($lineKey);
					$cartManager->saveCart($cart);
					$result = new \Change\Http\Web\Result\AjaxResult(array('cart' => $cart->toArray()));
					$event->setResult($result);
				}
			}
		}
	}

	public function update(CommerceServices $commerceServices, Event $event)
	{
		$request = $event->getRequest();
		$lineKey =  $request->getPost('lineKey', $request->getQuery('lineKey'));
		if ($lineKey)
		{
			$cart = $this->getCart($commerceServices);
			if ($cart)
			{
				$cartLine = $cart->getLineByKey($lineKey);
				if ($cartLine)
				{
					$parameters = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
					$cartManager = $commerceServices->getCartManager();
					if (isset($parameters['product']))
					{
						$product = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters['product']);
						if ($product instanceof \Rbs\Commerce\Interfaces\CartLineConfigCapable)
						{
							$cartLineConfig = $product->getCartLineConfig($commerceServices, $parameters);
							if (($oldLine = $cart->getLineByKey($cartLineConfig->getKey())) !== null)
							{
								$e = new \RuntimeException('Duplicate Line: ' . $oldLine->getNumber() , 999999);
								$e->httpStatusCode = HttpResponse::STATUS_CODE_409;
								throw $e;
							}

							$cartLine = $cartManager->updateLineByKey($cart, $lineKey, $cartLineConfig);
							$lineKey = $cartLine->getKey();
						}
					}

					if (isset($parameters['quantity']))
					{
						$cartManager->updateLineQuantityByKey($cart, $lineKey, intval($parameters['quantity']));
					}

					$cartManager->saveCart($cart);
					$result = new \Change\Http\Web\Result\AjaxResult(array('cart' => $cart->toArray()));
					$event->setResult($result);
				}
			}
		}
	}
}