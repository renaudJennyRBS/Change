<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Collection;

use Change\Collection\CollectionArray;

/**
 * @name \Rbs\Order\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addProcessingStatuses(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices instanceof \Change\Services\ApplicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
			$statuses = array(
				\Rbs\Order\Documents\Order::PROCESSING_STATUS_EDITION,
				\Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING,
				\Rbs\Order\Documents\Order::PROCESSING_STATUS_FINALIZED,
				\Rbs\Order\Documents\Order::PROCESSING_STATUS_CANCELED
			);
			$items = array();
			foreach ($statuses as $s)
			{
				$items[$s] = $i18nManager->trans('m.rbs.order.admin.order_processingstatus_' . $s, array('ucf'));
			}
			$event->setParam('collection', new CollectionArray('Rbs_Order_Collection_ProcessingStatus', $items));
			$event->stopPropagation();
		}
	}
}