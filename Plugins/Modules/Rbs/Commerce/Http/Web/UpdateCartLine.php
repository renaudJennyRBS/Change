<?php
namespace Rbs\Commerce\Http\Web;

use Change\Http\Web\Event;
use Rbs\Commerce\CommerceServices;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Commerce\Http\Web\UpdateCartLine
 */
class UpdateCartLine extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			$request = $event->getRequest();
			if ($request->isDelete() || $request->getPost('delete', $request->getQuery('delete')))
			{
				$this->delete($commerceServices, $event);
				return;
			}
			else
			{
				$this->update($commerceServices, $event);
				return;
			}
		}
		throw new \RuntimeException('Unable to get CommerceServices', 999999);
	}

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @return null|\Rbs\Commerce\Cart\Cart
	 */
	protected function getCart(CommerceServices $commerceServices)
	{
		$cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		return ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
	}

	/**
	 * @param CommerceServices $commerceServices
	 * @param Event $event
	 */
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
					$cartManager->normalize($cart);
					$cartManager->saveCart($cart);

					(new GetCurrentCart())->execute($event);
				}
			}
		}
	}

	/**
	 * @param CommerceServices $commerceServices
	 * @param Event $event
	 */
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
					if (isset($parameters['quantity']))
					{
						$quantity = intval($parameters['quantity']);
						$cartManager = $commerceServices->getCartManager();
						if ($quantity > 0)
						{
							$cartManager->updateLineQuantityByKey($cart, $lineKey, $quantity);
						}
						else
						{
							$cart->removeLineByKey($lineKey);
						}
						$cartManager->normalize($cart);
						$cartManager->saveCart($cart);
					}

					(new GetCurrentCart())->execute($event);
				}
			}
		}
	}
}