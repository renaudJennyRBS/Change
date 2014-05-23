<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Rest\V1\CollectionResult;
use Change\Http\Rest\V1\Link;
use Change\Http\Rest\V1\Resources\DocumentLink;
use Zend\Http\Response;

/**
 * @name \Rbs\Catalog\Http\Rest\PriceResult
 */
class PriceResult
{

	public function productPriceCollection(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$startIndex = intval($request->getQuery('offset', 0));
		$maxResults = intval($request->getQuery('limit', 10));
		$areaId = intval($request->getQuery('areaId'));
		$webStoreId = intval($request->getQuery('webStoreId'));
		$startActivation = $request->getQuery('startActivation');
		$endActivation = $request->getQuery('endActivation');

		$sort = $request->getQuery('sort');
		$desc = ($request->getQuery('desc') == "true");
		if (\Change\Stdlib\String::isEmpty($sort))
		{
			$sort = 'priority';
		}

		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$result->addLink($selfLink);
		$result->setOffset($startIndex);
		$result->setLimit($maxResults);

		/* @var $product \Rbs\Catalog\Documents\Product */
		$product = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$model = $event->getApplicationServices()->getModelManager()->getModelByName('Rbs_Price_Price');
		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery($model);

		$conditions = array($query->eq('sku', $product->getSku()));
		if ($areaId)
		{
			$conditions[] = $query->eq('billingArea', $areaId);
		}
		if ($webStoreId)
		{
			$conditions[] = $query->eq('webStore', $webStoreId);
		}
		if ($startActivation)
		{
			$conditions[] = $query->gte('startActivation', \DateTime::createFromFormat(\DateTime::ISO8601, urldecode($startActivation)));
		}
		if ($endActivation)
		{
			$conditions[] = $query->lte('endActivation', \DateTime::createFromFormat(\DateTime::ISO8601, urldecode($endActivation)));
		}
		$query->andPredicates($conditions);

		if ($sort == 'webStore')
		{
			$qb = $query->getPropertyBuilder('webStore');
			$qb->addOrder('label', !$desc);
		}
		elseif ($sort == 'webStore' )
		{
			$qb = $query->getPropertyBuilder('billingArea');
			$qb->addOrder('label', !$desc);
		}
		else
		{
			$query->addOrder($sort, !$desc);
		}
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
				/* @var $document \Rbs\Price\Documents\Price */
				$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, $extraColumn));
			}
		}
		$result->setHttpStatusCode(Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}