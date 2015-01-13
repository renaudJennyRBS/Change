<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Http\Ajax;

/**
* @name \Rbs\Storelocator\Http\Ajax\Store
*/
class Store
{
	/**
	 * Default actionPath: Rbs/Storelocator/Store/{storeId}
	 * Event params:
	 *  - storeId
	 *  - website, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - data:
	 * @param \Change\Http\Event $event
	 */
	public function getData(\Change\Http\Event $event)
	{
		/** @var $store \Rbs\Storelocator\Documents\Store */
		$store = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('storeId'), 'Rbs_Storelocator_Store');
		$storelocatorServices = $event->getServices('Rbs_StorelocatorServices');
		if ($store && $storelocatorServices instanceof \Rbs\Storelocator\StorelocatorServices)
		{
			$event->setParam('detailed', true);
			$context = $event->paramsToArray();
			$storeData = $storelocatorServices->getStoreManager()->getStoreData($store, $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Storelocator/Store', $storeData);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Storelocator/Store/
	 * Event params:
	 *  - website, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - data:
	 *      coordinates
	 *      distance
	 *      commercialSign
	 * @param \Change\Http\Event $event
	 */
	public function getListData(\Change\Http\Event $event)
	{
		$storelocatorServices = $event->getServices('Rbs_StorelocatorServices');
		if ($storelocatorServices instanceof \Rbs\Storelocator\StorelocatorServices)
		{
			$event->setParam('detailed', false);
			$context = $event->paramsToArray();
			$data = $storelocatorServices->getStoreManager()->getStoresData($context);
			$pagination = $data['pagination'];
			$items = $data['items'];
			$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Storelocator/Store/', $items);
			$result->setPagination($pagination);
			$event->setResult($result);
		}
	}
} 