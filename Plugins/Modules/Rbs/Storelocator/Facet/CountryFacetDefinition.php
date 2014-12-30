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
* @name \Rbs\Storelocator\Facet\CountryFacetDefinition
*/
class CountryFacetDefinition extends \Rbs\Elasticsearch\Facet\DocumentFacetDefinition
{

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	function __construct(\Rbs\Elasticsearch\Documents\Facet $facet)
	{
		parent::__construct($facet);

		$this->mappingName  = 'countryCode';
		$this->fieldName = 'f_' . $this->mappingName;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @return array
	 */
	protected function getDefaultParameters()
	{
		return  ['multipleChoice' => false, 'showEmptyItem' => false];
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

	protected $collectionItems = null;

	/**
	 * @return array|null
	 */
	protected function getCollectionItemsTitle()
	{
		if ($this->collectionItems === null)
		{
			$this->collectionItems = [];
			$docQuery = $this->getDocumentManager()->getNewQuery('Rbs_Geo_Country');
			$selectQuery = $docQuery->andPredicates($docQuery->activated());
			$selectQuery->addOrder('code');

			/** @var \Change\Db\Query\Builder $dbQueryBuilder */
			$dbQueryBuilder = $selectQuery->dbQueryBuilder();
			$fb = $dbQueryBuilder->getFragmentBuilder();
			$dbQueryBuilder->addColumn($fb->getDocumentColumn('code'));
			$selectQuery = $dbQueryBuilder->query();
			$codes = $selectQuery->getResults($selectQuery->getRowsConverter()->addStrCol('code')->singleColumn('code'));
			$i18nManager = $this->getI18nManager();
			foreach ($codes as $code)
			{
				$this->collectionItems[$code] = $i18nManager->trans('m.rbs.geo.countries.' . strtolower($code), ['lc', 'ucf']);
			}
		}
		return $this->collectionItems;
	}

	/**
	 * @param array $context
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function getAggregation(array $context = [])
	{
		$mappingName = $this->getMappingName();
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
		$mappingName = $this->getMappingName();
		if (isset($aggregations[$mappingName]['buckets']))
		{
			$buckets = $aggregations[$mappingName]['buckets'];
			$this->formatListAggregation($av, $items, $buckets);
		}
		return $av;
	}
}