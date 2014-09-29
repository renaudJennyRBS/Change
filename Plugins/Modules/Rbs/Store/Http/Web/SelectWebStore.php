<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Store\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Store\Http\Web\SelectWebStore
 */
class SelectWebStore extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$request = $event->getRequest();
		$data = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/** @var $webStore \Rbs\Store\Documents\WebStore */
			$webStore = isset($data['webStoreId']) ? $documentManager->getDocumentInstance($data['webStoreId']) : null;
			/** @var $billingArea \Rbs\Price\Documents\BillingArea */
			$billingArea = isset($data['billingAreaId']) ? $documentManager->getDocumentInstance($data['billingAreaId']) : null;

			$zone = isset($data['zone']) ? $data['zone'] : null;

			if ($this->checkStoreAndArea($webStore, $billingArea))
			{
				$context = $commerceServices->getContext();
				$context->setWebStore($webStore);
				$context->setBillingArea($billingArea);

				if ($zone == null)
				{
					$zones = array();
					foreach ($billingArea->getTaxes() as $tax)
					{
						$zones = array_merge($zones, $tax->getZoneCodes());
					}
					$zones = array_unique($zones);
					if (count($zones) == 1)
					{
						$zone = $zones[0];
					}
				}

				if ($zone)
				{
					$context->setZone($zone);
				}

				$context->save();
				$cartManager = $commerceServices->getCartManager();
				$cartIdentifier = $context->getCartIdentifier();
				if ($cartIdentifier)
				{
					$this->updateCart($cartManager, $cartIdentifier, $webStore, $billingArea, $zone);
				}
				$event->setResult($this->getNewAjaxResult());
				return;
			}
		}

		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$message = $i18nManager->trans('m.rbs.store.front.error_invalid_parameters', ['ucf']);
		$result = $this->getNewAjaxResult(['errors' => array($message)]);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
		$event->setResult($result);
	}




	/**
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @param \Rbs\Price\Documents\BillingArea $billingArea
	 * @return bool
	 */
	protected function checkStoreAndArea($webStore, $billingArea)
	{
		if (!($webStore instanceof \Rbs\Store\Documents\WebStore))
		{
			return false;
		}
		if (!($billingArea instanceof \Rbs\Price\Documents\BillingArea))
		{
			return false;
		}
		return in_array($billingArea->getId(), $webStore->getBillingAreas()->getIds());
	}

	/**
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 * @param string $cartIdentifier
	 * @param \Rbs\Store\Documents\WebStore|null $webStore
	 * @param \Rbs\Price\Documents\BillingArea|null $billingArea
	 * @param string|null $zone
	 */
	protected function updateCart($cartManager, $cartIdentifier, $webStore, $billingArea, $zone)
	{
		$cart = $cartManager->getCartByIdentifier($cartIdentifier);
		if ($cart && !$cart->isLocked())
		{
			if ($webStore && $webStore->getId() != $cart->getWebStoreId())
			{
				$cart->setWebStoreId($webStore->getId());
			}

			if ($zone && $cart->getZone() != $zone)
			{
				$cart->setZone($zone);
				$cart->setBillingArea($billingArea);
				$cartManager->normalize($cart);
				$cartManager->saveCart($cart);
			}
		}
	}
}