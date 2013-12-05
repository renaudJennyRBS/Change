<?php
namespace Rbs\Commerce\Http\Rest\Action;

use Change\Http\Rest\Result\Link;
use Rbs\Commerce\Http\Rest\Result\CartResult;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Commerce\Http\Rest\Action\Cart
*/
class Cart
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function getCart($event)
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

	/**
	 * @param \Change\Http\Event $event
	 */
	public function insertCart($event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cart = $commerceServices->getCartManager()->getNewCart();
			if ($cart)
			{
				$event->setParam('cartIdentifier', $cart->getIdentifier());
				$cartData = $event->getRequest()->getPost()->toArray();
				if (is_array($cartData) && count($cartData))
				{
					$this->populateCart($commerceServices, $cart, $event->getRequest()->getPost()->toArray());
				}
				$commerceServices->getCartManager()->saveCart($cart);
				$this->getCart($event);

				$result = $event->getResult();
				if ($result instanceof CartResult)
				{
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);
				}
			}
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function updateCart($event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cartIdentifier = $event->getParam('cartIdentifier');
			$cartManager = $commerceServices->getCartManager();
			$cart = $cartManager->getCartByIdentifier($cartIdentifier);
			if ($cart)
			{
				$cartData = $event->getRequest()->getPost()->toArray();
				if (is_array($cartData) && count($cartData))
				{
					$this->populateCart($commerceServices, $cart, $cartData);
					$cartManager->saveCart($cart);
				}
				$this->getCart($event);
			}
		}
	}


	/**
	 * @param \Change\Http\Event $event
	 */
	public function deleteCart($event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cartIdentifier = $event->getParam('cartIdentifier');
			$cartManager = $commerceServices->getCartManager();
			$cart = $cartManager->getCartByIdentifier($cartIdentifier);
			if ($cart)
			{
				$cartStorage  = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($event->getApplicationServices()->getTransactionManager());
				$cartStorage->setDbProvider($event->getApplicationServices()->getDbProvider());
				$cartStorage->setDocumentManager($event->getApplicationServices()->getDocumentManager());
				$cartStorage->deleteCart($cart);
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $cartData
	 */
	protected function populateCart($commerceServices, $cart, $cartData)
	{
		if (isset($cartData['billingArea']))
		{
			$cart->setBillingArea($commerceServices->getPriceManager()->getBillingAreaByCode($cartData['billingArea']));
		}
		else
		{
			$cart->setBillingArea(null);
		}

		if (isset($cartData['zone']))
		{
			$cart->setZone($cartData['zone']);
		}
		elseif (array_key_exists('zone', $cartData))
		{
			$cart->setZone(null);
		}

		if (isset($cartData['webStoreId']))
		{
			$cart->setWebStoreId($cartData['webStoreId']);
		}
		elseif (array_key_exists('webStoreId', $cartData))
		{
			$cart->setWebStoreId(null);
		}

		if (isset($cartData['ownerId']))
		{
			$cart->setOwnerId($cartData['ownerId']);
		}
		elseif (array_key_exists('ownerId', $cartData))
		{
			$cart->setOwnerId(null);
		}

		if (isset($cartData['userId']))
		{
			$cart->setUserId($cartData['userId']);
		}
		elseif (array_key_exists('userId', $cartData))
		{
			$cart->setUserId(null);
		}

		if (isset($cartData['context']) && is_array($cartData['context']))
		{
			$cart->getContext()->fromArray($cartData['context']);
		}

		if (isset($cartData['lines']) && is_array($cartData['lines']))
		{
			$cm = $commerceServices->getCartManager();
			$cart->removeAllLines();

			foreach ($cartData['lines'] as $lineData)
			{
				$cm->addLine($cart, $lineData);
			}
		}
	}
}