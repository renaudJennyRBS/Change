<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Http\Rest;

use Change\Http\Rest\V1\CollectionResult;
use Change\Http\Rest\V1\Link;
use Change\Http\Rest\V1\Resources\DocumentLink;
use Zend\Http\Response;

/**
 * @name \Rbs\Productreturn\Http\Rest\ProductReturnResult
 */
class ProductReturnResult
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function orderProductReturnCollection(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$startIndex = intval($request->getQuery('offset', 0));
		$maxResults = intval($request->getQuery('limit', 50));
		$orderId = intval($event->getParam('documentId'));

		$sort = $request->getQuery('sort');
		$desc = ($request->getQuery('desc') == "true");
		if (\Change\Stdlib\String::isEmpty($sort))
		{
			$sort = 'code';
		}

		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$result->addLink($selfLink);
		$result->setOffset($startIndex);
		$result->setLimit($maxResults);

		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Productreturn_ProductReturn');
		$query->andPredicates($query->eq('orderId', $orderId));
		$query->addOrder($sort, !$desc);
		$result->setSort($sort);
		$result->setDesc($desc);

		$count = $query->getCountDocuments();
		$result->setCount($count);
		if ($count && $startIndex < $count)
		{
			$extraColumn = $event->getRequest()->getQuery('column', array());
			$collection = $query->getDocuments($startIndex, $maxResults);
			foreach ($collection as $document)
			{
				/* @var $document \Rbs\Order\Documents\Shipment */
				$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, $extraColumn));
			}
		}
		$result->setHttpStatusCode(Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}