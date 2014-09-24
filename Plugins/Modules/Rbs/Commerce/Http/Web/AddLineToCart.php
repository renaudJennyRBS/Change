<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 */
	public function add(CommerceServices $commerceServices, Event $event)
	{
		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());

		if (isset($arguments['modalInfos']))
		{
			$modalInfos = $arguments['modalInfos'];
			unset($arguments['modalInfos']);
		}

		$webStore = $commerceServices->getContext()->getWebStore();
		if (!$webStore)
		{
			$e = new \RuntimeException('Web Store is not defined.', 999999);
			$e->httpStatusCode = HttpResponse::STATUS_CODE_409;
			throw $e;
		}
		$cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
		if (!($cart instanceof \Rbs\Commerce\Cart\Cart) || $cart->isLocked())
		{
			$billingArea = $commerceServices->getContext()->getBillingArea();
			$zone = $commerceServices->getContext()->getZone();

			$cart = $commerceServices->getCartManager()->getNewCart($webStore, $billingArea, $zone);
			$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
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
					$e->httpStatusCode = HttpResponse::STATUS_CODE_409;
					throw $e;
				}
				$cart->setWebStoreId($webStore->getId());
				$cart->setPricesValueWithTax($webStore->getPricesValueWithTax());
				$cart->setBillingArea($commerceServices->getContext()->getBillingArea());
				$cart->setZone($commerceServices->getContext()->getZone());
			}
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
			$cartManager->normalize($cart);
			$cartManager->saveCart($cart);
		}
		else
		{
			$e = new \RuntimeException('Invalid line parameters.', 999999);
			$e->httpStatusCode = HttpResponse::STATUS_CODE_409;
			throw $e;
		}

		(new GetCurrentCart())->execute($event);
		$cartArray = $event->getResult()->toArray();
		$result = $this->getNewAjaxResult(['cart' => $cartArray, 'lineKey'=> $line->getKey()]);

		if (isset($modalInfos) && isset($modalInfos['productId']) && isset($modalInfos['sectionPageFunction']))
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$product = $documentManager->getDocumentInstance($modalInfos['productId']);
			$section = $documentManager->getDocumentInstance($modalInfos['sectionId']);
			$query = ['sectionPageFunction' => $modalInfos['sectionPageFunction']];
			if (isset($modalInfos['themeName']))
			{
				$query['themeName'] = $modalInfos['themeName'];
			}

			if ($product)
			{
				$urlManager = $event->getUrlManager();
				$absoluteUrl = $urlManager->absoluteUrl(true);
				if ($section instanceof \Change\Presentation\Interfaces\Section)
				{
					$url = $urlManager->getByDocument($product, $section, $query);
				}
				else
				{
					$url = $urlManager->getCanonicalByDocument($product, $query);
				}
				$urlManager->absoluteUrl($absoluteUrl);
				$result->setEntry('modalContentUrl', $url->normalize()->toString());
			}
		}
		$event->setResult($result);
	}
}