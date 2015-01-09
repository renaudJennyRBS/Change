<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Brand\Http\Ajax;

/**
 * @name \Rbs\Brand\Http\Ajax\Brand
 */
class Brand
{
	/**
	 * Default actionPath: Rbs/Brand/Brand/{brandId}
	 * Event params:
	 *  - brandId
	 *  - website, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 * @param \Change\Http\Event $event
	 */
	public function getData(\Change\Http\Event $event)
	{
		/** @var $brand \Rbs\Brand\Documents\Brand */
		$brand = $event->getApplicationServices()->getDocumentManager()
			->getDocumentInstance($event->getParam('brandId'), 'Rbs_Brand_Brand');

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($brand && $brand->published() && $commerceServices)
		{
			$event->setParam('detailed', true);
			$context = $event->paramsToArray();
			$brandManager = $commerceServices->getBrandManager();
			$brandData = $brandManager->getBrandData($brand, $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Brand/Brand', $brandData);
			$event->setResult($result);
		}
	}
} 