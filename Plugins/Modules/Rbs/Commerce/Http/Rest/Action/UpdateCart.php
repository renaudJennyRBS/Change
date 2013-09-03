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
				$cartData = $event->getRequest()->getPost()->toArray();
				if (is_array($cartData) && count($cartData))
				{
					$this->populateCart($commerceServices, $cart, $cartData);
					$cart->getCommerceServices()->getCartManager()->saveCart($cart);
				}
				(new GetCart())->execute($event);
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param array $cartData
	 */
	protected function populateCart($commerceServices, $cart, $cartData)
	{
		if (isset($cartData['billingArea']))
		{
			$cart->setBillingArea($commerceServices->getPriceManager()->getBillingAreaByCode($cartData['billingArea']));
		}
		elseif (array_key_exists('billingArea', $cartData))
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
				$configLine = new \Rbs\Commerce\Cart\CartLineConfig($commerceServices, $lineData);
				$cm->addLine($cart, $configLine, $configLine->getQuantity());
			}
		}
	}
}