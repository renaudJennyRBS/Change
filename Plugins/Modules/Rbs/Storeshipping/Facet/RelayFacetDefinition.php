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
* @name \Rbs\Storeshipping\Facet\RelayFacetDefinition
*/
class RelayFacetDefinition implements \Rbs\Elasticsearch\Facet\FacetDefinitionInterface
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
		return $this->i18nManager->trans('m.Storeshipping.front.relay_facet_title', ['ucf']);
	}


	public function getMappingName()
	{
		return 'allowRelayMode';
	}

	/**
	 * Unique facet identifier for URL Query
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->getMappingName();
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
			$this->getMappingName() => ['type' => 'boolean']
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
	 * @return \Rbs\Storeshipping\Facet\RelayFacetDefinition[]
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

				if ($key == 'false')
				{
					$terms[] = false;
				}
				elseif ($key == 'true')
				{
					$terms[] = true;
				}
			}

			if (count($terms))
			{
				return new \Elastica\Filter\Terms($this->getMappingName(), $terms);
			}
		}
		return null;
	}

	/**
	 * @param array $context
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function getAggregation(array $context = [])
	{
		$mappingName = $this->getMappingName();
		$aggregation = new \Elastica\Aggregation\Terms($mappingName);
		$aggregation->setSize(0);
		$aggregation->setField($mappingName);
		return $aggregation;
	}

	/**
	 * @param $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations)
	{
		$av = new \Rbs\Elasticsearch\Facet\AggregationValues($this);
		$mappingName = $this->getMappingName();
		if (isset($aggregations[$mappingName]['buckets']))
		{
			$buckets = $aggregations[$mappingName]['buckets'];
			foreach ($buckets as $bucket)
			{
				$count = isset($bucket['doc_count']) && $bucket['doc_count'] ? $bucket['doc_count'] : 0;
				if ($bucket['key'])
				{
					$v = new \Rbs\Elasticsearch\Facet\AggregationValue('true', $count,
						$this->i18nManager->trans('m.generic.front.yes', ['ucf']));
				}
				else
				{
					$v = new \Rbs\Elasticsearch\Facet\AggregationValue('false', $count,
						$this->i18nManager->trans('m.generic.front.no', ['ucf']));
				}
				$av->addValue($v);
			}
		}
		return $av;
	}
}