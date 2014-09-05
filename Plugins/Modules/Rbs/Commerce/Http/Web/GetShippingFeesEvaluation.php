<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Web;

use Rbs\Commerce\CommerceServices;

/**
* @name \Rbs\Commerce\Http\Web\GetShippingFeesEvaluation
*/
class GetShippingFeesEvaluation extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			$cartManager = $commerceServices->getCartManager();
			$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
			$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
			$shippingFeesEvaluation = [];
			if ($cart)
			{
				$webStoreId = $cart->getWebStoreId();
				$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($webStoreId);
				if ($webStore instanceof \Rbs\Store\Documents\WebStore)
				{
					$orderProcess = $webStore->getOrderProcess();
					$shippingFeesEvaluation = $commerceServices->getProcessManager()->getShippingFeesEvaluation($orderProcess, $cart, $event->getWebsite());
				}
			}
			$result = $this->getNewAjaxResult($shippingFeesEvaluation);
			$event->setResult($result);
			return;
		}
	}
}