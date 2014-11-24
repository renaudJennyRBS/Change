<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Events;

/**
 * @name \Rbs\Elasticsearch\Events\CatalogManager
 */
class CatalogManager
{
	/**
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Generic\GenericServices
	 */
	protected function getGenericServices($event)
	{
		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			throw new \RuntimeException('Generic services not set', 999999);
		}
		return $genericServices;
	}

	/**
	 * Input params: context
	 * Output param: productsData, pagination
	 * @param \Change\Events\Event $event
	 */
	public function onGetProductsData(\Change\Events\Event $event)
	{
		/** @var $productsDataContext array */
		$productsDataContext = $event->getParam('context');
		if (!is_array($productsDataContext))
		{
			return;
		}
		$applicationServices = $event->getApplicationServices();
		$logging = $event->getApplication()->getLogging();
		$documentManager = $applicationServices->getDocumentManager();
		$genericServices = $this->getGenericServices($event);
		$website = isset($productsDataContext['website']) ? $productsDataContext['website'] : null;
		$storeIndex = null;
		if ($website instanceof \Rbs\Website\Documents\Website)
		{
			$storeIndex = $genericServices->getIndexManager()->getStoreIndexByWebsite($website, $documentManager->getLCID());
		}
		if (!$storeIndex)
		{
			return;
		}

		$indexManager = $genericServices->getIndexManager();
		$client = $indexManager->getElasticaClient($storeIndex->getClientName());
		if (!$client)
		{
			$logging->warn(__METHOD__ . ': invalid client ' . $storeIndex->getClientName());
			return;
		}

		$index = $client->getIndex($storeIndex->getName());
		if (!$index->exists())
		{
			$logging->warn(__METHOD__ . ': index not exist ' . $storeIndex->getName());
			return;
		}

		$data = $productsDataContext['data'];
		$list = (isset($data['listId'])) ? intval($data['listId']) : null;
		if (is_numeric($list))
		{
			$list = $documentManager->getDocumentInstance($list);
		}

		if ($list instanceof \Rbs\Catalog\Documents\ProductList)
		{
			$data['listSortBy'] = $list->getProductSortOrder() . '.' . $list->getProductSortDirection();
		}

		$availableInWarehouseId = isset($data['showUnavailable']) && $data['showUnavailable'] ? null : 0;
		$facetFilters = isset($data['facetFilters']) ? $data['facetFilters'] : null;
		$searchText = isset($data['searchText']) ? $data['searchText'] : null;


		$queryHelper = new \Rbs\Elasticsearch\Index\QueryHelper($storeIndex, $indexManager, $genericServices->getFacetManager());
		$query = $queryHelper->getProductListQuery($list, $availableInWarehouseId, $searchText);
		if (is_array($facetFilters) && count($facetFilters))
		{
			$facets = $storeIndex->getFacetsDefinition();
			$filter = $queryHelper->getFacetsFilter($facets, $facetFilters, $data);
			if ($filter)
			{
				$query->setFilter($filter);
			}
		}
		$sortBy = isset($data['sortBy']) ? $data['sortBy'] : null;
		if ($sortBy && count(explode('.', $sortBy)) != 2)
		{
			$sortBy = null;
		};
		$queryHelper->addSortArgs($query, $sortBy, $data);
		$pagination = (isset($productsDataContext['pagination']) && is_array($productsDataContext['pagination'])) ? $productsDataContext['pagination'] : [];
		$pagination += ['offset' => 0, 'limit' => 10];
		$query->setFrom($pagination['offset'])->setSize($pagination['limit']);

		if ($event->getApplication()->inDevelopmentMode()) {
			$logging->info(json_encode($query->toArray()));
		}

		$searchResult = $index->getType($storeIndex->getDefaultTypeName())->search($query);
		$pagination['count'] = $totalCount = $searchResult->getTotalHits();


		$products = [];
		if ($totalCount)
		{
			$results = $searchResult->getResults();
			if (!count($results)) {
				$pagination['offset'] = 0;
				$query->setFrom($pagination['offset']);
				$searchResult = $index->getType($storeIndex->getDefaultTypeName())->search($query);
				$results = $searchResult->getResults();
			}

			/* @var $result \Elastica\Result */
			foreach ($results as $result)
			{
				$product = $documentManager->getDocumentInstance($result->getId());
				if ($product instanceof \Rbs\Catalog\Documents\Product && $product->published())
				{
					$products[] = $product;
				}
			}
		}
		$event->setParam('products', $products);
		$event->setParam('pagination', $pagination);
	}
} 