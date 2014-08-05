<?php
/**
 * Copyright (C) 2014 Loic Couturier
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Web;

/**
 * @name \Rbs\Commerce\Http\Web\GetShippingZonesCode
 */
class GetShippingZonesCode extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$zonesCode = [];
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cartManager = $commerceServices->getCartManager();
			$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
			$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
			if ($cart)
			{
				$orderProcess = $commerceServices->getProcessManager()->getOrderProcessByCart($cart);
				if ($orderProcess)
				{
					$orderProcess = $commerceServices->getContext()->getWebStore()->getOrderProcess();
					$documentList = $commerceServices->getProcessManager()->getShippingZones($orderProcess, $cart);

					foreach ($documentList as $document)
					{
						/** @var $document \Rbs\Geo\Documents\Zone */
						$zonesCode[] = $document->getCode();
					}
				}
			}
		}
		$result = $this->getNewAjaxResult($zonesCode);
		$event->setResult($result);
	}
}