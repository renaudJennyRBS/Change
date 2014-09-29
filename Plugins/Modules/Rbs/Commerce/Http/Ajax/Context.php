<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Ajax;

/**
* @name \Rbs\Commerce\Http\Ajax\Context
*/
class Context
{
	/**
	 * Default actionPath: Rbs/Commerce/Context
	 * Event params:
	 *  - data: webStoreId, billingAreaId, zone
	 * @param \Change\Http\Event $event
	 */
	public function set(\Change\Http\Event $event)
	{
		$data = $event->getParam('data');
		if (!is_array($data) || !count($data))
		{
			return;
		}
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/** @var $webStore \Rbs\Store\Documents\WebStore */
			$webStore = isset($data['webStoreId']) ? $documentManager->getDocumentInstance($data['webStoreId']) : null;

			/** @var $billingArea \Rbs\Price\Documents\BillingArea */
			$billingArea = isset($data['billingAreaId']) ? $documentManager->getDocumentInstance($data['billingAreaId']) : null;

			$zone = array_key_exists('zone', $data )? $data['zone'] : false;

			$context = $commerceServices->getContext();

			$context->setWebStore($webStore);
			$context->setBillingArea($billingArea);

			if ($zone === false)
			{
				$zones = array();
				foreach ($billingArea->getTaxes() as $tax)
				{
					$zones = array_merge($zones, $tax->getZoneCodes());
				}

				$zones = array_values(array_unique($zones));
				if (count($zones))
				{
					$zone = $zones[0];
				}
				else
				{
					$zone = null;
				}
			}
			$context->setZone($zone);
			$context->save();

			//refresh variables
			$webStore = $context->getWebStore();
			$billingArea = $context->getBillingArea();
			$zone = $context->getZone();

			$cartIdentifier = $context->getCartIdentifier();
			if ($cartIdentifier)
			{
				$cartManager = $commerceServices->getCartManager();
				$this->updateCart($cartManager, $cartIdentifier, $webStore, $billingArea, $zone);
			}

			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Commerce/Context',
				['common' => ['webStoreId' => $webStore ? $webStore->getId() : 0, 'billingAreaId' => $billingArea ? $billingArea->getId() : 0, 'zone' => $zone]]);
			$event->setResult($result);
			return;
		}
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

			if ($cart->getZone() != $zone || $billingArea !== $cart->getBillingArea())
			{
				$cart->setZone($zone);
				$cart->setBillingArea($billingArea);
				$cartManager->normalize($cart);
				$cartManager->saveCart($cart);
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Commerce/ContextConfiguration
	 * Event params:
	 *  - website
	 *  - data: availableWebStoreIds
	 * @param \Change\Http\Event $event
	 */
	public function getConfiguration(\Change\Http\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$event->setParam('detailed', true);
			$configurationData = $commerceServices->getContext()->getConfigurationData($event->paramsToArray());
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Commerce/ContextConfiguration', $configurationData);
			$event->setResult($result);
			return;
		}
	}
} 