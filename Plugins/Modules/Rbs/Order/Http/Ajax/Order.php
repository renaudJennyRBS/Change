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
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Order\OrderManager
	 */
	protected $orderManager;

	/**
	 * @var array
	 */
	protected $context;

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

		$this->orderManager = $commerceServices->getOrderManager();
		$this->documentManager = $event->getApplicationServices()->getDocumentManager();

		$this->context = $event->paramsToArray();
		$orderData = $this->orderManager->getOrderData($event->getParam('orderId'), $this->context);

		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Order/Order', $orderData);
		$event->setResult($result);
	}

	/**
	 * Default actionPath: Rbs/Order/Order/
	 *
	 * @param \Change\Http\Event $event
	 */
	public function getOrderList(\Change\Http\Event $event) {

	}
} 