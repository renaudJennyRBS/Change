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
* @name \Rbs\Commerce\Http\Ajax\Process
*/
class Process
{
	/**
	 * Default actionPath: Rbs/Commerce/Process/[processId]
	 * Event params:
	 *  - processId
	 *  - data:
	 * @param \Change\Http\Event $event
	 */
	public function getData(\Change\Http\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$context = $event->paramsToArray();
			if (!isset($context['data']['cartId'])) {
				$context['data']['cartId'] = $commerceServices->getContext()->getCartIdentifier();
			}
			$processData = $commerceServices->getProcessManager()->getProcessData($event->getParam('processId'), $context);
			if (count($processData))
			{
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Commerce/Process', $processData);
				$event->setResult($result);
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Commerce/Process/[processId]/ShippingModesByAddress/
	 * Event params:
	 *  - *processId
	 *  - data:
	 *    - cartId
	 *    - *address
	 * @param \Change\Http\Event $event
	 */
	public function getShippingModesByAddress(\Change\Http\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$event->setParam('detailed', false);
			$context = $event->paramsToArray();
			if (!isset($context['data']['cartId'])) {
				$context['data']['cartId'] = $commerceServices->getContext()->getCartIdentifier();
			}
			$shippingModesData = $commerceServices->getProcessManager()->getShippingModesDataByAddress($event->getParam('processId'), $context);
			$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Commerce/Process/ShippingModesByAddress/', $shippingModesData);
			$result->setPaginationCount(count($shippingModesData));
			$event->setResult($result);
		}
	}
} 