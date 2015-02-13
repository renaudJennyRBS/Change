<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\ProductSkuThresholdFacetDefinition
 */
class ProductSkuThresholdFacetDefinition extends \Rbs\Elasticsearch\Facet\DocumentFacetDefinition
{

	function __construct(\Rbs\Elasticsearch\Documents\Facet $facet)
	{
		parent::__construct($facet);
		$this->mappingName = 'stocks';
	}

	/**
	 * @return array
	 */
	protected function getDefaultParameters()
	{
		return ['thresholdCollectionId' => null, 'showEmptyItem' => false, 'renderingMode' => 'checkbox'];
	}

	/**
	 * @param \Rbs\Elasticsearch\Documents\Facet $facet
	 */
	public function validateConfiguration($facet)
	{
		$facet->setIndexCategory('store');
		$validParameters = $this->getDefaultParameters();
		$currentParameters = $facet->getParameters();
		foreach ($currentParameters as $name => $value)
		{
			switch ($name)
			{
				case 'thresholdCollectionId':
					if ($value)
					{
						$coll = $this->getDocumentManager()->getDocumentInstance($value, 'Rbs_Collection_Collection');
						if ($coll)
						{
							$validParameters[$name] = $coll->getId();
						}
					}
					break;
				case 'showEmptyItem':
					$validParameters[$name] = $value === 'false' ? false : boolval($value);
					break;
				case 'renderingMode':
					// TODO validate...
					$validParameters[$name] = $value;
					break;
			}
		}
		if (!isset($validParameters['thresholdCollectionId']))
		{
			$coll = $this->getCollectionByCode('Rbs_Stock_Collection_Threshold');
			if ($coll)
			{
				$validParameters['thresholdCollectionId'] = $coll->getId();
			}
		}
		$facet->getParameters()->fromArray($validParameters);
	}

	/**
	 * @param array $facetFilters
	 * @param array $context
	 * @return \Elastica\Filter\AbstractFilter[]
	 */
	public function getFiltersQuery(array $facetFilters, array $context = [])
	{
		/** @var $thresholds \Elastica\Query\Term[] */
		$context = $this->addWarehouseId($context);

		$thresholds = [];
		$orFilters = [];
		$filterName = $this->getFieldName();
		if (isset($facetFilters[$filterName]) && is_array($facetFilters[$filterName]))
		{
			$facetFilter = $facetFilters[$filterName];

			foreach ($facetFilter as $key => $subFacetFilter)
			{
				$key = strval($key);
				if (!empty($key))
				{
					$andFilters = [];
					$threshold = new \Elastica\Query\Term(['stocks.threshold' => $key]);
					$thresholds[] = $threshold;
					if ($this->hasChildren())
					{
						$andFilters[] = $this->buildThresholdsFilter([$threshold], $context);
						if (is_array($subFacetFilter))
						{
							foreach ($this->getChildren() as $childFacet)
							{
								$subFilter = $childFacet->getFiltersQuery($subFacetFilter, $context);
								if ($subFilter)
								{
									$andFilters[] = $subFilter;
								}
							}
						}
					}

					if (count($andFilters) == 1)
					{
						$orFilters[] = $andFilters[0];
					}
					elseif (count($andFilters) > 1)
					{
						$and = new \Elastica\Filter\Bool();
						foreach ($andFilters as $f)
						{
							$and->addMust($f);
						}
						$orFilters[] = $and;
					}
				}
			}
		}

		if (count($orFilters) == 1)
		{
			return $orFilters[0];
		}
		elseif (count($orFilters) > 1)
		{
			$filter = new \Elastica\Filter\Bool();
			foreach ($orFilters as $orFilter)
			{
				$filter->addShould($orFilter);
			}
			return $filter;
		}
		elseif (count($thresholds))
		{
			return $this->buildThresholdsFilter($thresholds, $context);
		}
		return null;
	}

	/**
	 * @param \Elastica\Query\Term[] $thresholds
	 * @param array $context
	 * @return \Elastica\Filter\Nested
	 */
	protected function buildThresholdsFilter($thresholds, array $context)
	{
		$warehouseId = intval($context['warehouseId']);
		$filterQuery = new \Elastica\Filter\Nested();
		$filterQuery->setPath('stocks');
		$nestedBool = new \Elastica\Query\Bool();
		$nestedBool->addMust(new \Elastica\Query\Term(['stocks.warehouseId' => $warehouseId]));
		foreach ($thresholds as $threshold)
		{
			$nestedBool->addShould($threshold);
		}
		$nestedBool->setMinimumNumberShouldMatch(1);
		$filterQuery->setQuery($nestedBool);
		return $filterQuery;
	}

	/**
	 * @param array $context
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function getAggregation(array $context = [])
	{
		/** @var $thresholds \Elastica\Query\Term[] */
		$context = $this->addWarehouseId($context);

		$nestedPrice = new \Elastica\Aggregation\Nested('stocks', 'stocks');
		$contextFilter = new \Elastica\Aggregation\Filter('context');
		$warehouseId = intval($context['warehouseId']);

		$bool = new \Elastica\Filter\Bool();
		$bool->addMust(new \Elastica\Filter\Term(['stocks.warehouseId' => $warehouseId]));
		$contextFilter->setFilter($bool);

		$field = 'stocks.threshold';
		$term = (new \Elastica\Aggregation\Terms('threshold'))->setField($field);

		$this->aggregateChildren($term, $context);

		$contextFilter->addAggregation($term);
		$nestedPrice->addAggregation($contextFilter);

		return $nestedPrice;
	}

	/**
	 * @param $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations)
	{
		$collectionId = $this->getParameters()->get('thresholdCollectionId');
		$items = $this->getCollectionItemsTitle($collectionId);

		$av = new \Rbs\Elasticsearch\Facet\AggregationValues($this);
		if (isset($aggregations['stocks']['context']['threshold']['buckets']))
		{
			$buckets = $aggregations['stocks']['context']['threshold']['buckets'];
			if ($items)
			{
				$this->formatListAggregation($av, $items, $buckets);
			}
			else
			{
				$this->formatKeyAggregation($av, $buckets);
			}
		}
		return $av;
	}

	/**
	 * @param array $context
	 * @return array
	 */
	protected function addWarehouseId(array $context)
	{
		if (!isset($context['warehouseId']))
		{
			$context['warehouseId'] = 0;
			if (isset($context['webStoreId']) && $context['webStoreId'])
			{
				$webStore = $this->documentManager->getDocumentInstance($context['webStoreId']);
				if ($webStore instanceof \Rbs\Store\Documents\WebStore)
				{
					$context['warehouseId'] = $webStore->getWarehouseId();
					return $context;
				}
				return $context;
			}
			return $context;
		}
		return $context;
	}
}