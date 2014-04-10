<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Stock\Http\Rest\Actions\GetReservations
 */
class GetReservations
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$result = new \Change\Http\Rest\Result\CollectionResult();

		if (($limit = $event->getRequest()->getQuery('limit', 20)) !== null)
		{
			$result->setLimit(intval($limit));
		}

		if (($offset = $event->getRequest()->getQuery('offset', 0)) !== null)
		{
			$result->setOffset(intval($offset));
		}

		$total = 0;
		$reservations = array();

		$cs = $event->getServices('commerceServices');
		if ($cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$stockManager = $cs->getStockManager();

			$skuId = $event->getRequest()->getQuery('skuId');

			// Get count of total reservations
			$total = $stockManager->countReservationsBySku($skuId);

			// Get list of reservations
			$tmpReservations = $stockManager->getReservationsBySku($skuId, null, $limit, $offset, 'date', 'desc');

			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$urlManager = $event->getUrlManager();
			$vc = new \Change\Http\Rest\ValueConverter($urlManager, $documentManager);
			foreach ($tmpReservations as $reservation)
			{
				$store = $documentManager->getDocumentInstance($reservation['store_id'], 'Rbs_Store_WebStore');

				if ($store instanceof \Rbs\Store\Documents\WebStore)
				{
					$reservation['store'] = $vc->toRestValue($store, \Change\Documents\Property::TYPE_DOCUMENT)->toArray();
				}

				$targetId = $stockManager->getTargetIdFromTargetIdentifier($reservation['target']);
				if ($targetId != null)
				{
					$targetObj = $documentManager->getDocumentInstance($targetId);
					if ($targetObj != null)
					{
						$reservation['targetInstance'] = $vc->toRestValue($targetObj, \Change\Documents\Property::TYPE_DOCUMENT)
							->toArray();
					}
				}

				$reservations[] = $reservation;
			}
		}
		$result->setResources($reservations);

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setCount($total);
		$event->setResult($result);
	}

}