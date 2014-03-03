<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\Link;

/**
 * @name \Rbs\Catalog\Documents\ProductList
 */
class ProductList extends \Compilation\Rbs\Catalog\Documents\ProductList
{
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$documentResult = $restResult;
			$selfLinks = $documentResult->getRelLink('self');
			$selfLink = array_shift($selfLinks);
			if ($selfLink instanceof Link)
			{
				$pathParts = explode('/', $selfLink->getPathInfo());
				$link = new Link($documentResult->getUrlManager(),
					implode('/', $pathParts) . '/ProductListItems/', 'productListItems');
				$documentResult->addLink($link);
			}
		}
	}
}