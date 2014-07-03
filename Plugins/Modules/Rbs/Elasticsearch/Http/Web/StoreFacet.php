<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Elasticsearch\Http\Web\StoreFacet
*/
class StoreFacet extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		$result = [];
		$request = $event->getRequest();

		$website = $event->getWebsite();
		$genericServices = $this->getGenericServices($event);
		$commerceServices = $this->getCommerceServices($event);
		if ($commerceServices == null || $genericServices == null || !($website instanceof \Rbs\Website\Documents\Website) ||
		 !$request->getPost('indexId'))
		{
			$result['error'] = 'invalid parameters';
			$webResult = $this->getNewAjaxResult($result);
			$webResult->setHttpStatusCode(HttpResponse::STATUS_CODE_409);
			$event->setResult($webResult);
		}
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();

		/** @var $storeIndex \Rbs\Elasticsearch\Documents\StoreIndex */
		$storeIndex = $documentManager->getDocumentInstance($request->getPost('indexId'), 'Rbs_Elasticsearch_StoreIndex');
		if (!$storeIndex)
		{
			$result['error'] = 'invalid store index';
			$webResult = $this->getNewAjaxResult($result);
			$webResult->setHttpStatusCode(HttpResponse::STATUS_CODE_409);
			$event->setResult($webResult);
		}

		$commerceContext = $request->getPost('commerceContext');
		$productListId = intval($request->getPost('toDisplayDocumentId'));

		/** @var $productList \Rbs\Catalog\Documents\ProductList */
		$productList = null;
		if ($productListId)
		{
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) || !$productList->activated())
			{
				$result['error'] = 'Invalid product list';
				$webResult = $this->getNewAjaxResult($result);
				$webResult->setHttpStatusCode(HttpResponse::STATUS_CODE_409);
				$event->setResult($webResult);
			}
		}

		$facetIds =  $request->getPost('facets');
		if (!is_array($facetIds) || !count($facetIds))
		{
			$result['error'] = 'no facet defined';
			$webResult = $this->getNewAjaxResult($result);
			$webResult->setHttpStatusCode(HttpResponse::STATUS_CODE_409);
			$event->setResult($webResult);
		}

		$facets = $genericServices->getFacetManager()->resolveFacetIds($facetIds);

		$queryFilters = $request->getPost('facetFilters', null);
		$facetFilters = $this->validateQueryFilters($queryFilters);
		$showUnavailable = $request->getPost('showUnavailable');
		$availableInWarehouseId = $showUnavailable ? null : 0;

		$indexManager = $genericServices->getIndexManager();
		$client = $indexManager->getElasticaClient($storeIndex->getClientName());
		if (!$client)
		{
			$result['error'] = 'Invalid client ' . $storeIndex->getClientName();
			$webResult = $this->getNewAjaxResult($result);
			$webResult->setHttpStatusCode(HttpResponse::STATUS_CODE_409);
			$event->setResult($webResult);
		}

		$index = $client->getIndex($storeIndex->getName());
		if (!$index->exists())
		{
			$result['error'] = 'Index not exist ' . $storeIndex->getName();
			$webResult = $this->getNewAjaxResult($result);
			$webResult->setHttpStatusCode(HttpResponse::STATUS_CODE_409);
			$event->setResult($webResult);
		}

		$queryHelper = new \Rbs\Elasticsearch\Index\QueryHelper($storeIndex, $indexManager, $genericServices->getFacetManager());

		$query = $queryHelper->getProductListQuery($productList, $availableInWarehouseId);
		$queryHelper->addFilteredFacets($query, $facets, $facetFilters, $commerceContext);

		$searchResult = $index->getType($storeIndex->getDefaultTypeName())->search($query);

		$facetsValues = $queryHelper->formatAggregations($searchResult->getAggregations(), $facets);
		if (count($facetFilters))
		{
			$queryHelper->applyFacetFilters($facetsValues, $facetFilters);
		}

		foreach ($facetsValues as $aggregationValues)
		{
			$result[] = $aggregationValues->toArray();
		}

		$event->setResult($this->getNewAjaxResult($result));
	}

	/**
	 * @param Event $event
	 * @return \Rbs\Generic\GenericServices | null
	 */
	protected function getGenericServices($event)
	{
		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			return null;
		}
		return $genericServices;
	}

	/**
	 * @param Event $event
	 * @return \Rbs\Commerce\CommerceServices
	 */
	protected function getCommerceServices($event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			return null;
		}
		return $commerceServices;
	}

	/**
	 * @param $queryFilters
	 * @return array
	 */
	protected function validateQueryFilters($queryFilters)
	{
		$facetFilters = array();
		if (is_array($queryFilters))
		{
			foreach ($queryFilters as $fieldName => $rawValue)
			{
				if (is_string($fieldName) && $rawValue)
				{
					$facetFilters[$fieldName] = $rawValue;
				}
			}
			return $facetFilters;
		}
		return $facetFilters;
	}
} 