<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Wishlist\Http\Web;

use Change\Http\Web\Event;

/**
* @name \Rbs\Wishlist\Http\Web\AddProductsToCart
*/
class AddProductsToCart extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$data = $event->getRequest()->getPost()->toArray();
			$productIds = isset($data['productIds']) ? $data['productIds'] : null;

			if (is_array($productIds))
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();
				$commerceServices = $event->getServices('commerceServices');
				/* @var $commerceServices \Rbs\Commerce\CommerceServices */
				$cartManager = $commerceServices->getCartManager();
				if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
				{
					$cart = $this->getCart($commerceServices, $cartManager, $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser());
					$this->addProducts($productIds, $documentManager, $cartManager, $cart);
				}
			}

			(new \Rbs\Commerce\Http\Web\GetCurrentCart())->execute($event);
			$cartArray = $event->getResult()->toArray();
			$result = $this->getNewAjaxResult(['cart' => $cartArray]);
			$event->setResult($result);
		}
	}

	/**
	 * TODO: this function is replicated from \Rbs\Commerce\Http\Web\AddLineToCart. Refactor this
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 * @param \Change\User\UserInterface $currentUser
	 * @return \Rbs\Commerce\Cart\Cart
	 * @throws \RuntimeException
	 */
	protected function getCart($commerceServices, $cartManager, $currentUser)
	{
		$webStore = $commerceServices->getContext()->getWebStore();
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
		if (!($cart instanceof \Rbs\Commerce\Cart\Cart) || $cart->isLocked())
		{
			$billingArea = $commerceServices->getContext()->getBillingArea();
			$zone = $commerceServices->getContext()->getZone();

			$cart = $commerceServices->getCartManager()->getNewCart($webStore, $billingArea, $zone);
			$cart->setUserId($currentUser->getId());
			$cart->getContext()->set('userName', $currentUser->getName());

			$commerceServices->getContext()->setCartIdentifier($cart->getIdentifier());
			$commerceServices->getContext()->save();
		}
		else
		{
			if ($cart->getWebStoreId() != $webStore->getId())
			{
				if (!$cart->isEmpty())
				{
					$e = new \RuntimeException('Invalid webstore.', 999999);
					$e->httpStatusCode = \Zend\Http\Response::STATUS_CODE_409;
					throw $e;
				}
				$cart->setWebStoreId($webStore->getId());
				$cart->setPricesValueWithTax($webStore->getPricesValueWithTax());
				$cart->setBillingArea($commerceServices->getContext()->getBillingArea());
				$cart->setZone($commerceServices->getContext()->getZone());
			}
		}
		return $cart;
	}

	/**
	 * @param integer[] $productIds
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @throws \RuntimeException
	 */
	protected function addProducts($productIds, $documentManager, $cartManager, $cart)
	{
		foreach ($productIds as $productId => $toAddToCart)
		{
			$product = $toAddToCart ? $documentManager->getDocumentInstance($productId) : null;
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$sku = $product->getSku();
				if ($sku instanceof \Rbs\Stock\Documents\Sku)
				{
					$cartLineParameters = [
						'key' => $productId,
						'designation' => $product->getCurrentLocalization()->getTitle(),
						'quantity' => 1, //TODO
						'options' => ['productId' => $productId],
						'items' => [
							['codeSKU' => $product->getSku()->getCode()]
						]
					];
					$line = $cart->getNewLine($cartLineParameters);
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
						$cartManager->normalize($cart);
						$cartManager->saveCart($cart);
					}
					else
					{
						$e = new \RuntimeException('Invalid line parameters.', 999999);
						$e->httpStatusCode = \Zend\Http\Response::STATUS_CODE_409;
						throw $e;
					}
				}
			}
		}
	}
}