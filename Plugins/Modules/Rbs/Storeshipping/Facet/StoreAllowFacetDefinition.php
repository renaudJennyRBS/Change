<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Facet;

/**
* @name \Rbs\Storeshipping\Facet\StoreAllowFacetDefinition
*/
class StoreAllowFacetDefinition implements \Rbs\Elasticsearch\Facet\FacetDefinitionInterface
{
	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $parameters;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 */
	public function __construct(\Change\I18n\I18nManager $i18nManager)
	{
		$this->i18nManager = $i18nManager;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->i18nManager->trans('m.Storeshipping.front.store_allow_facet_title', ['ucf']);
	}

	/**
	 * @return string[]
	 */
	public function getMappingNames()
	{
		return ['allowRelayMode', 'allowPickUp', 'allowPayment'];
	}

	/**
	 * Unique facet identifier for URL Query
	 * @return string
	 */
	public function getFieldName()
	{
		return 'storeAllow';
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters()
	{
		if ($this->parameters === null)
		{
			$this->parameters = new \Zend\Stdlib\Parameters();
		}
		return $this->parameters;
	}

	/**
	 * Part of index mapping
	 * @return array
	 */
	public function getMapping()
	{
		return ['document' => [
			'allowRelayMode' => ['type' => 'boolean'],
			'allowPickUp' => ['type' => 'boolean'],
			'allowPayment' => ['type' => 'boolean']
		]];
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetDefinitionInterface|null
	 */
	public function getParent()
	{
		return null;
	}

	/**
	 * @return boolean
	 */
	public function hasChildren()
	{
		return false;
	}

	/**
	 * @return \Rbs\Storeshipping\Facet\StoreAllowFacetDefinition[]
	 */
	public function getChildren()
	{
		return [];
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $documentData
	 * @return array
	 */
	public function addIndexData($document, array $documentData)
	{
		if ($document instanceof \Rbs\Storelocator\Documents\Store)
		{
			$documentData['allowRelayMode'] = $document->getAllowRelayMode();
			$documentData['allowPickUp'] = $document->getAllowPickUp();
			$documentData['allowPayment'] = $document->getAllowPayment();
		}
		return $documentData;
	}

	/**
	 * @param array $facetFilters
	 * @param array $context
	 * @return \Elastica\Filter\AbstractFilter|null
	 */
	public function getFiltersQuery(array $facetFilters, array $context = [])
	{
		$filterName = $this->getFieldName();
		if (isset($facetFilters[$filterName]) && is_array($facetFilters[$filterName]))
		{
			$facetFilter =  $facetFilters[$filterName];
			$terms = [];
			foreach ($facetFilter as $key => $ignored)
			{
				$term = ($ignored == "1");
				switch ($key) {
					case 'allowRelayMode':
						$terms[] =  new \Elastica\Filter\Terms('allowRelayMode', [$term]);
						break;
					case 'allowPickUp':
						$terms[] =  new \Elastica\Filter\Terms('allowPickUp', [$term]);
						break;
					case 'allowPayment':
						$terms[] =  new \Elastica\Filter\Terms('allowPayment', [$term]);
						break;
				}
			}

			if (count($terms))
			{
				$and = (isset($facetFilter['and']) == true);
				$filter = new \Elastica\Filter\Bool();
				foreach($terms as $termFilter) {
					if ($and) {
						$filter->addMust($termFilter);
					} else {
						$filter->addShould($termFilter);
					}
				}
				return $filter;
			}
		}
		return null;
	}

	/**
	 * @param array $context
	 * @return \Elastica\Aggregation\AbstractAggregation[]
	 */
	public function getAggregation(array $context = [])
	{
		$aggregations = [];
		foreach ($this->getMappingNames() as $mappingName)
		{
			$aggregation = new \Elastica\Aggregation\Terms($mappingName);
			$aggregation->setSize(0);
			$aggregation->setField($mappingName);
			$aggregations[] = $aggregation;
		}
		return $aggregations;
	}

	/**
	 * @param $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations)
	{
		$av = new \Rbs\Elasticsearch\Facet\AggregationValues($this);
		foreach ($this->getMappingNames() as $mappingName)
		{
			if (isset($aggregations[$mappingName]['buckets']))
			{
				$buckets = $aggregations[$mappingName]['buckets'];
				foreach ($buckets as $bucket)
				{
					$count = isset($bucket['doc_count']) && $bucket['doc_count'] ? $bucket['doc_count'] : 0;
					if ($bucket['key'] && $count)
					{
						$i18nKey = 'm.storeshipping.front.facet_value_' . \Change\Stdlib\String::snakeCase($mappingName);
						$v = new \Rbs\Elasticsearch\Facet\AggregationValue($mappingName, $count,
							$this->i18nManager->trans($i18nKey, ['ucf']));
						$av->addValue($v);
					}
				}
			}
		}
		return $av;
	}
}