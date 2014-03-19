<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Job;

use \Rbs\Order\Documents\Order;

/**
 * @name \Rbs\Order\Job\OrderComplete
 */
class OrderComplete
{
	public function execute(\Change\Job\Event $event)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$stockManager = $commerceServices->getStockManager();

			$job = $event->getJob();

			$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($job->getArgument('orderId'));
			if ($order instanceof Order)
			{
				$processingStatus = $order->getProcessingStatus();
				//if order processing status is finalized or canceled, cleanup the reservation
				if ($processingStatus == Order::PROCESSING_STATUS_FINALIZED || $processingStatus == Order::PROCESSING_STATUS_CANCELED)
				{
					$stockManager->unsetReservations($order->getIdentifier());
				}
			}
		}
		else
		{
			$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
		}
	}
} 