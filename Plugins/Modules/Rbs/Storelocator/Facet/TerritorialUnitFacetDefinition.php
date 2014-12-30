<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Facet;

/**
* @name \Rbs\Storelocator\Facet\TerritorialUnitFacetDefinition
*/
class TerritorialUnitFacetDefinition extends \Rbs\Elasticsearch\Facet\DocumentFacetDefinition
{
	/**
	 * @var string
	 */
	protected $unitType;

	function __construct(\Rbs\Elasticsearch\Documents\Facet $facet)
	{
		parent::__construct($facet);
		$this->unitType = $this->getParameters()->get('unitType');
		$this->mappingName  = strtolower($this->unitType);
		$this->fieldName = 'f_' . $this->mappingName;
	}

	/**
	 * @return array
	 */
	protected function getDefaultParameters()
	{
		return  ['unitType' => 'DEPARTEMENT', 'multipleChoice' => false, 'showEmptyItem' => false];
	}

	/**
	 * @return string
	 */
	protected function getUnitType()
	{
		return $this->unitType;
	}

	public function getMapping()
	{
		$mappingName = $this->getMappingName();
		return ['document' => [
			$mappingName => ['type' => 'string', 'index' => 'not_analyzed'],
			$mappingName.'_id' => ['type' => 'long'],
			$mappingName.'_code' => ['type' => 'string', 'index' => 'not_analyzed']
		]];
	}

	/**
	 * @param \Rbs\Elasticsearch\Documents\Facet $facet
	 */
	public function validateConfiguration($facet)
	{
		$facet->setIndexCategory('storeLocator');
		$validParameters = $this->getDefaultParameters();
		$currentParameters = $facet->getParameters();
		foreach ($currentParameters as $name => $value)
		{
			switch ($name) {
				case 'unitType':
					if (!\Change\Stdlib\String::isEmpty($value))
					{
						$validParameters[$name] = strval($value);
					}
					break;
				case 'showEmptyItem':
				case 'multipleChoice':
					$validParameters[$name] = $value === 'false' ? false : boolval($value);
					break;
			}
		}
		$facet->getParameters()->fromArray($validParameters);
	}

	public function addIndexData($document, array $documentData)
	{
		if ($document instanceof \Rbs\Storelocator\Documents\Store)
		{
			$unitType = $this->getUnitType();
			$tu = $document->getTerritorialUnit();

			while ($tu)
			{
				if ($tu->getUnitType() == $unitType)
				{
					$mappingName = $this->getMappingName();
					$documentData[$mappingName] = $tu->getTitle();
					$documentData[$mappingName.'_id'] = $tu->getId();
					$documentData[$mappingName.'_code'] = $tu->getCode();
					break;
				} else {
					$tu = $tu->getUnitParent();
				}
			}
		}
		return $documentData;
	}

	/**
	 * @param array $facetFilters
	 * @param array $context
	 * @return \Elastica\Filter\AbstractFilter[]
	 */
	public function getFiltersQuery(array $facetFilters, array $context = [])
	{
		$terms = [];
		$orFilters = [];

		$filterName = $this->getFieldName();
		if (isset($facetFilters[$filterName]) && is_array($facetFilters[$filterName]))
		{
			$facetFilter = $facetFilters[$filterName];
			foreach ($facetFilter as $term => $subFacetFilter)
			{
				$andFilters = [];
				$terms[] = $term;
				if ($this->hasChildren())
				{
					$andFilters[] = new \Elastica\Filter\Term([$this->getMappingName() . '_id' => $term]);
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
					$and =  new \Elastica\Filter\Bool();
					foreach ($andFilters as $f)
					{
						$and->addMust($f);
					}
					$orFilters[] =$and;
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
		elseif (count($terms))
		{
			return new \Elastica\Filter\Terms($this->getMappingName() . '_id', $terms);
		}
		return null;
	}


	protected $collectionItems = null;

	/**
	 * @return array|null
	 */
	protected function getCollectionItemsTitle()
	{
		if ($this->collectionItems === null)
		{
			$docQuery = $this->getDocumentManager()->getNewQuery('Rbs_Geo_TerritorialUnit');
			$selectQuery = $docQuery->andPredicates($docQuery->eq('unitType', $this->unitType));
			$dbQueryBuilder = $selectQuery->dbQueryBuilder();
			$fb = $dbQueryBuilder->getFragmentBuilder();
			$dbQueryBuilder->addColumn($fb->alias($fb->getDocumentColumn('id'), 'id'))
				->addColumn($fb->getDocumentColumn('title'));

			$selectQuery = $dbQueryBuilder->query();
			$this->collectionItems = $selectQuery->getResults($selectQuery->getRowsConverter()->addIntCol('id')->addStrCol('title')->singleColumn('title')->indexBy('id'));

		}
		return $this->collectionItems;
	}

	/**
	 * @param array $context
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function getAggregation(array $context = [])
	{
		$mappingName = $this->getMappingName() . '_id';
		$aggregation = new \Elastica\Aggregation\Terms($mappingName);
		if ($this->getParameters()->get('showEmptyItem'))
		{
			$aggregation->setMinimumDocumentCount(0);
		}
		$aggregation->setField($mappingName);
		$this->aggregateChildren($aggregation, $context);
		return $aggregation;
	}

	/**
	 * @param $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations)
	{
		$items = $this->getCollectionItemsTitle();
		$av = new \Rbs\Elasticsearch\Facet\AggregationValues($this);
		$mappingName = $this->getMappingName() . '_id';

		if (isset($aggregations[$mappingName]['buckets']))
		{
			$buckets = $aggregations[$mappingName]['buckets'];
			$this->formatListAggregation($av, $items, $buckets);
		}
		return $av;
	}
}