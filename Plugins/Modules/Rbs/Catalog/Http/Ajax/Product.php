<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Http\Ajax;

/**
* @name \Rbs\Catalog\Http\Ajax\Product
*/
class Product
{
	/**
	 * Default actionPath: Rbs/Catalog/Product/{productId}
	 * Event params:
	 *  - productId
	 *  - website, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - data:
	 *      webStoreId,
	 *      billingAreaId,
	 *      zone,
	 *      quantity
	 * @param \Change\Http\Event $event
	 */
	public function getData(\Change\Http\Event $event)
	{
		/** @var $product \Rbs\Catalog\Documents\Product */
		$product = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('productId'), 'Rbs_Catalog_Product');

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($product && $commerceServices)
		{
			$event->setParam('detailed', true);
			$context = $event->paramsToArray();
			$catalogManager = $commerceServices->getCatalogManager();
			$productData = $catalogManager->getProductData($product, $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Catalog/Product', $productData);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Catalog/Product/
	 * Event params:
	 *  - website, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - data:
	 *      listId
	 *      sortBy
	 *      showUnavailable
	 *      warehouseId
	 *      webStoreId
	 *      billingAreaId
	 *      zone
	 *      quantity
	 *      facetFilters
	 *      searchText
	 * @param \Change\Http\Event $event
	 */
	public function getListData(\Change\Http\Event $event)
	{
		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices)
		{
			$event->setParam('detailed', false);
			$context = $event->paramsToArray();
			$catalogManager = $commerceServices->getCatalogManager();
			$data = $catalogManager->getProductsData($context);
			$pagination = $data['pagination'];
			$items = $data['items'];
			$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Catalog/Product/', $items);
			$result->setPagination($pagination);
			$event->setResult($result);
		}
	}
} 