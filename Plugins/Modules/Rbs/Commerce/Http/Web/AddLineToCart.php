<?php
namespace Rbs\Commerce\Http\Web;

use Change\Http\Web\Event;
use Rbs\Commerce\CommerceServices;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Commerce\Http\Web\AddLineToCart
 */
class AddLineToCart extends \Change\Http\Web\Actions\AbstractAjaxAction
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

		$cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
		if (!($cart instanceof \Rbs\Commerce\Cart\Cart))
		{
			$webStore = $commerceServices->getContext()->getWebStore();
			if (!$webStore)
			{
				$e = new \RuntimeException('Web Store is not defined.', 999999);
				$e->httpStatusCode = HttpResponse::STATUS_CODE_409;
				throw $e;
			}
			$billingArea = $commerceServices->getContext()->getBillingArea();
			$zone = $commerceServices->getContext()->getZone();

			$context['userId'] = $event->getAuthenticationManager()->getCurrentUser()->getId();
			$cart = $commerceServices->getCartManager()->getNewCart($webStore, $billingArea, $zone, $context);
			$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
			$cart->setUserId($currentUser->getId());
			$cart->getContext()->set('userName', $currentUser->getName());

			$commerceServices->getContext()->setCartIdentifier($cart->getIdentifier());
			$commerceServices->getContext()->save();
		}

		$line = $cart->getNewLine($arguments);
		if ($line->getKey() && ($line->getQuantity() > 0) && count($line->getItems()))
		{
			$previousLine = $cartManager->getLineByKey($cart, $line->getKey());
			if ($previousLine)
			{
				$cartManager->updateLineQuantityByKey($cart, $line->getKey(), $previousLine->getQuantity() + $line->getQuantity());
			}
			else
			{
				$cartManager->addLine($cart, $line);
			}
			$cartManager->saveCart($cart);
		}
		else
		{
			$e = new \RuntimeException('Invalid line parameters.', 999999);
			$e->httpStatusCode = HttpResponse::STATUS_CODE_409;
			throw $e;
		}

		$result = $this->getNewAjaxResult(array('cart' => $cart->toArray(), 'lineKey' => $line->getKey()));
		$event->setResult($result);
	}
}