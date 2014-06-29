<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\DocumentFacetDefinition
 */
class DocumentFacetDefinition implements FacetDefinitionInterface
{
	const PARAM_MAPPING_NAME = 'mappingName';

	/**
	 * @var null|string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $fieldName;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $parameters;

	/**
	 * @var \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[]
	 */
	protected $children = [];

	/**
	 * @var \Rbs\Elasticsearch\Facet\FacetDefinitionInterface
	 */
	protected $parent;

	/**
	 * @var string
	 */
	protected $mappingName;

	/**
	 * @param \Rbs\Elasticsearch\Documents\Facet $facet
	 */
	function __construct(\Rbs\Elasticsearch\Documents\Facet $facet)
	{
		$this->fieldName = 'f_' . $facet->getId();
		$this->parameters = $facet->getParameters();
		$this->parameters->set('configurationType', $facet->getConfigurationType());
		$this->title = $facet->getCurrentLocalization()->getTitle();
		if (!$this->title)
		{
			$this->title = $facet->getRefLocalization()->getTitle();
		}
	}

	/**
	 * @return string
	 */
	protected function getMappingName()
	{
		return $this->mappingName;
	}

	/**
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->fieldName;
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
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return boolean
	 */
	public function hasChildren()
	{
		return count($this->children) > 0;
	}

	/**
	 * @return FacetDefinitionInterface[]
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface[] $children
	 * @return $this
	 */
	public function setChildren(array $children)
	{
		$this->children = $children;
		return $this;
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\FacetDefinitionInterface|null $parent
	 * @return $this
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $documentData
	 * @return array
	 */
	public function addIndexData($document, array $documentData)
	{
		return $documentData;
	}

	/**
	 * Part of index mapping
	 * @return array
	 */
	public function getMapping()
	{
		return [];
	}

	/**
	 * @param array $facetFilters
	 * @param array $context
	 * @return \Elastica\Filter\AbstractFilter[]
	 */
	public function getFiltersQuery(array $facetFilters, array $context = [])
	{
		$filtersQuery = [];
		$filterName = $this->getFieldName();
		if (isset($facetFilters[$filterName]))
		{
			$facetFilter = is_array($facetFilters[$filterName]) ? $facetFilters[$filterName] : [$facetFilters[$filterName]];
			$terms = [];
			foreach ($facetFilter as $key)
			{
				$key = strval($key);
				if (!empty($key))
				{
					$terms[] = $key;
				}
			}
			if (count($terms))
			{
				$filterQuery = new \Elastica\Filter\Terms($this->getMappingName(), $terms);
				$filtersQuery[] = $filterQuery;
			}
		}
		return $filtersQuery;
	}

	/**
	 * @param array $context
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function getAggregation(array $context = [])
	{
		$mappingName = $this->getMappingName();
		$aggregation = new \Elastica\Aggregation\Terms($mappingName);
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
			foreach ($aggregations[$mappingName]['buckets'] as $bucket)
			{
				$v = new \Rbs\Elasticsearch\Facet\AggregationValue($bucket['key'], $bucket['doc_count']);
				$av->addValue($v);
			}
		}
		return $av;
	}
}