<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Http\Ajax;

/**
* @name \Rbs\Elasticsearch\Http\Ajax\StoreFacet
*/
class StoreFacet
{
	/**
	 * Default actionPath: Rbs/Elasticsearch/Index/{indexId}/Facets
	 * Event params:
	 *  - website, section, page
	 *  - dataSetNames
	 *  - visualFormats
	 *  - URLFormats
	 *  - data:
	 *      indexId
	 *      facetIds
	 *      facetFilters
	 *      searchText
	 *      webStoreId,
	 *      billingAreaId,
	 *      zone
	 *      showUnavailable
	 *      productListId
	 *      conditionId
	 * @param \Change\Http\Event $event
	 */
	public function getFacetsData(\Change\Http\Event $event)
	{
		$requestContext = $event->paramsToArray();
		$context = (isset($requestContext['data']) && is_array($requestContext['data'])) ? $requestContext['data'] : [];
		$context += ['facetIds' => [], 'facetFilters' => [],
			'webStoreId' => 0, 'billingAreaId' => 0, 'zone' => null,
			'searchText' => null, 'productListId' => 0, 'conditionId' => 0, 'showUnavailable' => true];

		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();

		/** @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');

		$searchText = strval($context['searchText']);
		if (\Change\Stdlib\String::isEmpty($searchText))
		{
			$searchText = null;
		}

		/** @var $storeIndex \Rbs\Elasticsearch\Documents\StoreIndex */
		$storeIndex = $documentManager->getDocumentInstance(intval($event->getParam('indexId')), 'Rbs_Elasticsearch_StoreIndex');
		if (!$storeIndex)
		{
			return;
		}
		$indexManager = $genericServices->getIndexManager();
		$client = $indexManager->getElasticaClient($storeIndex->getClientName());
		if (!$client)
		{
			return;
		}

		$index = $client->getIndex($storeIndex->getName());
		if (!$index->exists())
		{
			return;
		}

		$productListId = $context['productListId'];

		/** @var $productList \Rbs\Catalog\Documents\ProductList */
		$productList = null;
		if ($productListId)
		{
			$productList = $documentManager->getDocumentInstance($productListId);
			if (!($productList instanceof \Rbs\Catalog\Documents\ProductList) || !$productList->activated())
			{
				return;
			}
		}

		$facetIds =  $context['facetIds'];
		if (!is_array($facetIds) || !count($facetIds))
		{
			return;
		}

		$facets = $genericServices->getFacetManager()->resolveFacetIds($facetIds);
		$facetFilters = $this->validateQueryFilters( $context['facetFilters']);

		$availableInWarehouseId = null;
		if ($context['webStoreId'] && !$context['showUnavailable'])
		{
			$webStore = $documentManager->getDocumentInstance($context['webStoreId']);
			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				$availableInWarehouseId = $webStore->getWarehouseId();
			}
		}

		$queryHelper = new \Rbs\Elasticsearch\Index\QueryHelper($storeIndex, $indexManager, $genericServices->getFacetManager());

		$query = $queryHelper->getProductListQuery($productList, $availableInWarehouseId, $searchText);
		$queryHelper->addFilteredFacets($query, $facets, $facetFilters, $context);

		$searchResult = $index->getType($storeIndex->getDefaultTypeName())->search($query);

		$facetsValues = $queryHelper->formatAggregations($searchResult->getAggregations(), $facets);
		if (count($facetFilters))
		{
			$queryHelper->applyFacetFilters($facetsValues, $facetFilters);
		}

		$result = [];
		foreach ($facetsValues as $aggregationValues)
		{
			$result[] = $aggregationValues->toArray();
		}

		$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Elasticsearch/Facet/', $result);
		$result->setPaginationCount(count($result));
		$event->setResult($result);
	}


	/**
	 * @param $queryFilters
	 * @return array
	 */
	protected function validateQueryFilters($queryFilters)
	{
		$facetFilters = [];
		if (is_array($queryFilters) && count($queryFilters))
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