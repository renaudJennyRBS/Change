<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\ProductPriceFacetDefinition
 */
class ProductPriceFacetDefinition extends \Rbs\Elasticsearch\Facet\DocumentFacetDefinition
{
	function __construct(\Rbs\Elasticsearch\Documents\Facet $facet)
	{
		parent::__construct($facet);
		$this->mappingName  = 'prices';
	}

	/**
	 * @return array
	 */
	protected function getDefaultParameters()
	{
		return  ['withTax' => false, 'interval' => 50, 'minAmount' => null, 'maxAmount' => null];
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
			switch ($name) {
				case 'withTax':
					$validParameters[$name] = $value === 'false' ? false : boolval($value);
					break;
				case 'interval':
					if ($value)
					{
						$value = intval($value);
						if ($value > 0)
						{
							$validParameters[$name] = $value;
						}
					}
					break;
				case 'minAmount':
				case 'maxAmount':
					if ($value !== null)
					{
						$value = intval($value);
						if ($value >= 0)
						{
							$validParameters[$name] = $value;
						}
					}
					break;
			}
		}
		if (isset($validParameters['maxAmount']))
		{
			if ($validParameters['maxAmount'] < $validParameters['interval'])
			{
				$validParameters['maxAmount'] = $validParameters['interval'];
			}

			if (isset($validParameters['minAmount']))
			{
				if ($validParameters['maxAmount'] < $validParameters['minAmount'] + $validParameters['interval'])
				{
					$validParameters['maxAmount'] = $validParameters['minAmount'] + $validParameters['interval'];
				}
			}
		}
		$facet->getParameters()->fromArray($validParameters);
	}

	/**
	 * @param array $facetFilters
	 * @param array $context
	 * @return \Elastica\Filter\Nested|null
	 */
	public function getFilterQuery(array $facetFilters, array $context = [])
	{
		$filterName = $this->getFieldName();
		if (isset($facetFilters[$filterName]))
		{
			$facetFilter = is_array($facetFilters[$filterName]) ? $facetFilters[$filterName] : [$facetFilters[$filterName]];
			$ranges = [];
			$interval = $this->getParameters()->get('interval');
			$field = $this->getParameters()->get('withTax') ? 'prices.valueWithTax' : 'prices.value';

			foreach ($facetFilter as $key)
			{
				if (is_numeric($key))
				{
					$key = intval($key);
					$ranges[] = new \Elastica\Query\Range($field, ['gte' => $key, 'lt' => $key + $interval]);
				}
			}

			if (count($ranges))
			{
				$context = $context + ['now' => new \DateTime(), 'zone' => '', 'billingAreaId' => 0, 'storeId' => 0];
				$now = $context['now'];
				if ($now instanceof \DateTime) {$now = $now->format(\DateTime::ISO8601);}
				$zone = strval($context['zone']);
				$billingAreaId = intval($context['billingAreaId']);
				$storeId = intval($context['storeId']);

				$filterQuery = new \Elastica\Filter\Nested();
				$filterQuery->setPath('prices');
				$nestedBool = new \Elastica\Query\Bool();

				$nestedBool->addMust(new \Elastica\Filter\Term(['prices.billingAreaId' => $billingAreaId]));
				$nestedBool->addMust(new \Elastica\Filter\Term(['prices.zone' => $zone]));
				$nestedBool->addMust(new \Elastica\Filter\Term(['prices.storeId' => $storeId]));
				$nestedBool->addMust(new \Elastica\Filter\Range('prices.startActivation', ['lte' => $now]));
				$nestedBool->addMust(new \Elastica\Filter\Range('prices.endActivation', ['gt' => $now]));

				foreach ($ranges as $range)
				{
					$nestedBool->addShould($range);
				}

				$nestedBool->setMinimumNumberShouldMatch(1);
				$filterQuery->setQuery($nestedBool);
				return $filterQuery;
			}
		}
		return null;
	}

	/**
	 * @param array $context Expectex keys : now, zone, billingAreaId, storeId
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function getAggregation(array $context = [])
	{
		$context = $context + ['now' => new \DateTime(), 'zone' => '', 'billingAreaId' => 0, 'storeId' => 0];
		$nestedPrice = new \Elastica\Aggregation\Nested('prices', 'prices');

		$contextFilter = new \Elastica\Aggregation\Filter('context');
		$now = $context['now'];
		if ($now instanceof \DateTime) {$now = $now->format(\DateTime::ISO8601);}
		$zone = strval($context['zone']);
		$billingAreaId = intval($context['billingAreaId']);
		$storeId = intval($context['storeId']);

		$min = $this->getParameters()->get('minAmount');
		$max = $this->getParameters()->get('maxAmount');
		$field = $this->getParameters()->get('withTax') ? 'prices.valueWithTax' : 'prices.value';

		$bool = new \Elastica\Filter\Bool();
		$bool->addMust(new \Elastica\Filter\Term(['prices.billingAreaId' => $billingAreaId]));
		$bool->addMust(new \Elastica\Filter\Term(['prices.zone' => $zone]));
		$bool->addMust(new \Elastica\Filter\Term(['prices.storeId' => $storeId]));
		$bool->addMust(new \Elastica\Filter\Range('prices.startActivation', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('prices.endActivation', array('gt' => $now)));
		if ($min !== null)
		{
			$bool->addMust(new \Elastica\Filter\Range($field, ['gte' => $min]));
		}
		if ($max !== null)
		{
			$bool->addMust(new \Elastica\Filter\Range($field, ['lte' => $max]));
		}
		$contextFilter->setFilter($bool);


		$interval = $this->getParameters()->get('interval');
		$rangePrice = new \Elastica\Aggregation\Histogram('range_price', $field, $interval);
		$contextFilter->addAggregation($rangePrice);
		$nestedPrice->addAggregation($contextFilter);

		return $nestedPrice;
	}

	/**
	 * @param $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations)
	{
		$av = new \Rbs\Elasticsearch\Facet\AggregationValues($this);
		if (isset($aggregations['prices']['context']['range_price']['buckets']))
		{
			$buckets = $aggregations['prices']['context']['range_price']['buckets'];
			foreach ($buckets as $bucket)
			{
				$v = new \Rbs\Elasticsearch\Facet\AggregationValue($bucket['key'], $bucket['doc_count']);
				$av->addValue($v);
			}
		}
		return $av;
	}
}