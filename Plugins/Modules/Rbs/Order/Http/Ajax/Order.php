<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Http\Ajax;

/**
 * @name \Rbs\Order\Http\Ajax\Order
 */
class Order
{
	/**
	 * Default actionPath: Rbs/Order/Order/([0-9]+)
	 * event param : orderId
	 * @param \Change\Http\Event $event
	 */
	public function getOrder(\Change\Http\Event $event)
	{
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}

		$orderManager = $commerceServices->getOrderManager();
		$context = $event->paramsToArray();
		$orderData = $orderManager->getOrderData($event->getParam('orderId'), $context);

		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Order/Order', $orderData);
		$event->setResult($result);
	}

	/**
	 * Default actionPath: Rbs/Order/Order/
	 *
	 * @param \Change\Http\Event $event
	 */
	public function getOrderList(\Change\Http\Event $event)
	{
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}

		$orderManager = $commerceServices->getOrderManager();
		$context = $event->paramsToArray();
		$user = $event->getParam('user');
		$status = $event->getParam('processingStatus');
		$data = $orderManager->getOrdersData($user, [], $status, $context);
		$pagination = $data['pagination'];
		$items = $data['items'];

		$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Order/Order/', $items);
		$result->setPagination($pagination);
		$event->setResult($result);
	}
} 