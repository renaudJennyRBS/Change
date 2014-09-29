<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Index;

use Rbs\Elasticsearch\Facet\FacetManager;

/**
* @name \Rbs\Elasticsearch\Index\QueryHelper
*/
class QueryHelper
{
	/**
	 * @var FacetManager
	 */
	protected $facetManager;

	/**
	 * @var IndexManager
	 */
	protected $indexManager;

	/**
	 * @var IndexDefinitionInterface
	 */
	protected $index;

	/**
	 * @param IndexDefinitionInterface $index
	 * @param IndexManager $indexManager
	 * @param FacetManager $facetManager
	 */
	function __construct(IndexDefinitionInterface $index, IndexManager $indexManager, FacetManager $facetManager)
	{
		$this->index = $index;
		$this->indexManager = $indexManager;
		$this->facetManager = $facetManager;
	}

	/**
	 * @param $searchText
	 * @param array $allowedSectionIds
	 * @return \Elastica\Query
	 */
	public function getSearchQuery($searchText, array $allowedSectionIds = null)
	{
		$now = (new \DateTime())->format(\DateTime::ISO8601);
		if ($searchText)
		{
			$multiMatch = new \Elastica\Query\MultiMatch();
			$multiMatch->setQuery($searchText);
			$multiMatch->setFields(array('title', 'content'));
		}
		else
		{
			$multiMatch = new \Elastica\Query\MatchAll();
		}

		$bool = new \Elastica\Filter\Bool();
		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));

		if (is_array($allowedSectionIds))
		{
			$bool->addMust(new \Elastica\Filter\Terms('canonicalSectionId', $allowedSectionIds));
		}
		$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);
		$query = new \Elastica\Query($filtered);
		$query->setFields(['model', 'title']);
		return $query;
	}

	/**
	 * @param \Elastica\Query $query
	 * @return $this
	 */
	public function addHighlight(\Elastica\Query $query)
	{
		$query->setHighlight(['tags_schema' => 'styled', 'fields' => [
			'title' => ['number_of_fragments' => 0],
			'content' => [
				'fragment_size' => 150,
				'number_of_fragments' => 3,
				'no_match_size' => 150
			]
		]]);
		return $this;
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param integer $availableInWarehouseId
	 * @param string $searchText
	 * @return \Elastica\Query
	 */
	public function getProductListQuery($productList = null, $availableInWarehouseId = null, $searchText = null)
	{
		$now = (new \DateTime())->format(\DateTime::ISO8601);
		if ($searchText)
		{
			$multiMatch = new \Elastica\Query\MultiMatch();
			$multiMatch->setQuery($searchText);
			$multiMatch->setFields(array('title', 'content'));
		}
		else
		{
			$multiMatch = new \Elastica\Query\MatchAll();
		}
		$bool = new \Elastica\Filter\Bool();
		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));
		if ($productList)
		{
			$nested = new \Elastica\Filter\Nested();
			$nested->setPath('listItems');
			$nestedBool = new \Elastica\Query\Bool();
			$nestedBool->addMust(new \Elastica\Query\Term(['listId' => $productList->getId()]));
			$nested->setQuery($nestedBool);
			$bool->addMust($nested);
		}
		if ($availableInWarehouseId !== null)
		{
			$nested = new \Elastica\Filter\Nested();
			$nested->setPath('stocks');
			$nestedBool = new \Elastica\Query\Bool();
			$nestedBool->addMust(new \Elastica\Query\Term(['warehouseId' => $availableInWarehouseId]));
			$nestedBool->addMust(new \Elastica\Query\Range('availability', ['gt' => 0]));
			$nested->setQuery($nestedBool);
			$bool->addMust($nested);
		}

		$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);
		$query = new \Elastica\Query($filtered);
		return $query;
	}

	/**
	 * @param \Elastica\Query $query
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[] $facets
	 * @param array $context
	 * @return $this
	 */
	public function addFacets(\Elastica\Query $query, array $facets, array $context = [])
	{
		foreach ($facets as $facet)
		{
			$query->addAggregation($facet->getAggregation($context));
		}
		$query->setSize(0);
		return $this;
	}

	/**
	 * @param \Elastica\Query $query
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[] $facets
	 * @param array $facetFilters
	 * @param array $context
	 * @return $this
	 */
	public function addFilteredFacets(\Elastica\Query $query, array $facets, array $facetFilters, array $context = [])
	{
		foreach ($facets as $facet)
		{
			$customFilter = $facetFilters;
			unset($customFilter[$facet->getFieldName()]);
			$aggregation = $facet->getAggregation($context);
			if (count($customFilter))
			{
				$filter = $this->getFacetsFilter($facets, $customFilter, $context);
				if ($filter)
				{
					$aggFilter = new \Elastica\Aggregation\Filter('filtered__' . $aggregation->getName());
					$aggFilter->setFilter($filter);
					$aggFilter->addAggregation($aggregation);
					$query->addAggregation($aggFilter);
					continue;
				}
			}
			$query->addAggregation($aggregation);
		}
		$query->setSize(0);
		return $this;
	}

	/**
	 * @param array $aggregations
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[] $facets
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues[]
	 */
	public function formatAggregations(array $aggregations, array $facets)
	{
		foreach ($aggregations as $name => $val)
		{
			if (strpos($name, 'filtered__') === 0)
			{
				$name = substr($name, 10);
				if (is_array($val) && isset($val[$name]))
				{
					$aggregations[$name] = $val[$name];
				}
			}
		}

		$formattedAggregations = [];
		foreach ($facets as $facet)
		{
			$formattedAggregations[] = $facet->formatAggregation($aggregations);
		}
		return $formattedAggregations;
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\AggregationValues[] $aggregationValues
	 * @param array $facetFilters
	 */
	public function applyFacetFilters(array $aggregationValues, array $facetFilters)
	{
		foreach ($aggregationValues as $aggValues)
		{
			if (isset($facetFilters[$aggValues->getFieldName()]))
			{
				$facetFilter = $facetFilters[$aggValues->getFieldName()];
				if (is_array($facetFilter))
				{
					foreach ($aggValues->getValues() as $aggValue)
					{
						$key = strval($aggValue->getKey());
						if (isset($facetFilter[$key]))
						{
							$aggValue->setSelected(true);
							if (is_array($facetFilter[$key]) && $aggValue->hasAggregationValues())
							{
								$this->applyFacetFilters($aggValue->getAggregationValues(), $facetFilter[$key]);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[] $facets
	 * @param array $facetFilters
	 * @param array $context
	 * @return \Elastica\Filter\Bool|null
	 */
	public function getFacetsFilter(array $facets, array $facetFilters, array $context)
	{
		$filters = [];
		foreach ($facets as $facet)
		{
			$filter = $facet->getFiltersQuery($facetFilters, $context);
			if ($filter)
			{
				$filters[] = $filter;
			}
		}

		if (count($filters))
		{
			$bool = new \Elastica\Filter\Bool();
			foreach ($filters as $filter)
			{
				$bool->addMust($filter);
			}
			return $bool;
		}
		return null;
	}

	/**
	 * Accepted sortBy title.[asc|.desc], dateAdded.[asc|.desc], price.[asc|.desc]
	 * @param \Elastica\Query $query
	 * @param string $sortBy
	 * @param array $context Expected key listId, listSortBy, webStoreId, billingAreaId, zone
	 */
	public function addSortArgs($query, $sortBy, array $context)
	{
		$sort = [];
		if ($sortBy == null && isset($context['listId']))
		{
			$sort['position'] = ['order' => 'asc', 'nested_path' => 'listItems', 'nested_filter' => ['term'=>['listId' => $context['listId']]]];
			$sortBy = $context['listSortBy'];
		}

		if ($sortBy)
		{
			list($sortName, $sortDir) = explode('.', $sortBy);
			if ($sortName && ($sortDir == 'asc' || $sortDir == 'desc'))
			{
				switch ($sortName) {
					case 'title' :
						$sort['title.untouched'] = ['order' => $sortDir];
						break;
					case 'dateAdded' :
						if (isset($context['listId']))
						{
							$sort['creationDate'] = ['order' => $sortDir, 'nested_path' => 'listItems', 'nested_filter' => ['term'=>['listId' => $context['listId']]]];
						}
						else
						{
							$sort['creationDate'] = ['order' => $sortDir];
						}
						break;
					case 'price' :
						$webStoreId = isset($context['webStoreId']) ? $context['webStoreId'] : 0;
						$billingAreaId = isset($context['billingAreaId']) ? $context['billingAreaId'] : 0;
						if ($billingAreaId && $webStoreId)
						{
							$zone = isset($context['zone']) ? $context['zone'] : '';
							$now = (new \DateTime())->format(\DateTime::ISO8601);
							$bool = new \Elastica\Filter\Bool();
							$bool->addMust(new \Elastica\Filter\Term(['billingAreaId' => $billingAreaId]));
							$bool->addMust(new \Elastica\Filter\Term(['storeId' => $webStoreId]));
							$bool->addMust(new \Elastica\Filter\Term(['zone' => $zone ? $zone : '']));
							$bool->addMust(new \Elastica\Filter\Range('startActivation', array('lte' => $now)));
							$bool->addMust(new \Elastica\Filter\Range('endActivation', array('gt' => $now)));
							$sort['valueWithTax'] = ['order' => $sortDir, 'nested_path' => 'prices', 'nested_filter' => $bool->toArray()];
						}
						break;
					case 'threshold' :
						$warehouseId = isset($context['warehouseId']) ? $context['warehouseId'] : 0;
						$bool = new \Elastica\Filter\Bool();
						$bool->addMust(new \Elastica\Filter\Term(['warehouseId' => $warehouseId]));
						$sort['thresholdIndex'] = ['order' => $sortDir, 'nested_path' => 'stocks', 'nested_filter' => $bool->toArray()];
						break;
				}
			}
		}

		if (count($sort))
		{
			$query->setSort($sort);
		}
	}
}