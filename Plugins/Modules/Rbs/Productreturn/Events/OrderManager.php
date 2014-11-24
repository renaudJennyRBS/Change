<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Events;

/**
 * @name \Rbs\Productreturn\Events\OrderManager
 */
class OrderManager
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onProductreturnGetShipmentData(\Change\Events\Event $event)
	{
		$shipmentData = $event->getParam('shipmentData');
		if ($shipmentData)
		{
			$shipment = $event->getParam('shipment');
			if ($shipment instanceof \Rbs\Productreturn\Documents\Shipment)
			{
				$shipmentData['common']['productReturnId'] = $shipment->getId();
				$event->setParam('shipmentData', $shipmentData);
			}
		}
	}
}