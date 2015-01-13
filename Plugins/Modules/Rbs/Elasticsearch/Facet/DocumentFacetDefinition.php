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
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

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
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param string $code
	 * @return \Rbs\Collection\Documents\Collection|null
	 */
	protected function getCollectionByCode($code)
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');
		return $query->andPredicates($query->eq('code', $code))->getFirstDocument();
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
	 * @return \Rbs\Elasticsearch\Facet\FacetDefinitionInterface|null
	 */
	public function getParent()
	{
		return $this->parent;
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
					$andFilters[] = new \Elastica\Filter\Term([$this->getMappingName() => $term]);
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
			return new \Elastica\Filter\Terms($this->getMappingName(), $terms);
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
		if ($this->getParameters()->get('showEmptyItem'))
		{
			$aggregation->setMinimumDocumentCount(0);
		}
		$aggregation->setField($mappingName);
		$this->aggregateChildren($aggregation, $context);
		return $aggregation;
	}

	/**
	 * @param \Elastica\Aggregation\AbstractAggregation $aggregation
	 * @param array $context
	 */
	protected function aggregateChildren($aggregation, array $context)
	{
		if ($this->hasChildren())
		{
			foreach ($this->getChildren() as $children)
			{
				$aggregation->addAggregation($children->getAggregation($context));
			}
		}
	}

	/**
	 * @param $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations)
	{
		$collectionId = $this->getParameters()->get('collectionId');
		$items = $this->getCollectionItemsTitle($collectionId);

		$av = new \Rbs\Elasticsearch\Facet\AggregationValues($this);
		$mappingName = $this->getMappingName();
		if (isset($aggregations[$mappingName]['buckets']))
		{
			$buckets = $aggregations[$mappingName]['buckets'];
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
	 * @param \Rbs\Elasticsearch\Facet\AggregationValues $av
	 * @param array $buckets
	 */
	protected function formatKeyAggregation($av, $buckets)
	{
		foreach ($buckets as $bucket)
		{
			$v = new \Rbs\Elasticsearch\Facet\AggregationValue($bucket['key'], $bucket['doc_count']);
			$av->addValue($v);
			$this->formatChildren($v, $bucket);
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\AggregationValues $av
	 * @param \Callable $callable
	 * @param array $buckets
	 */
	protected function formatCallableTitleAggregation($av, $callable, $buckets)
	{
		foreach ($buckets as $bucket)
		{
			$title = call_user_func($callable, $bucket['key']);
			$v = new \Rbs\Elasticsearch\Facet\AggregationValue($bucket['key'], $bucket['doc_count'], $title);
			$av->addValue($v);
			$this->formatChildren($v, $bucket);
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\AggregationValues $av
	 * @param array $items
	 * @param array $buckets
	 */
	protected function formatListAggregation($av, $items, $buckets)
	{
		$bucketByKey = [];
		foreach ($buckets as $bucket)
		{
			$bucketByKey[$bucket['key']] = $bucket;
		}

		$showEmptyItem = $this->getParameters()->get('showEmptyItem');
		foreach ($items as $key => $title)
		{
			$bucket = isset($bucketByKey[$key]) ? $bucketByKey[$key] : [];
			if ($showEmptyItem || count($bucket))
			{
				$count = isset($bucket['doc_count']) && $bucket['doc_count'] ? $bucket['doc_count'] : 0;
				$v = new \Rbs\Elasticsearch\Facet\AggregationValue($key, $count, $title);
				$av->addValue($v);
				$this->formatChildren($v, $bucket);
			}
		}
	}

	/**
	 * @param integer|null $collectionId
	 * @return array|null
	 */
	protected function getCollectionItemsTitle($collectionId)
	{
		$items = null;
		if ($collectionId)
		{
			$collection = $this->getDocumentManager()->getDocumentInstance($collectionId);
			if ($collection instanceof \Rbs\Collection\Documents\Collection)
			{
				$items = [];
				foreach ($collection->getItems() as $item)
				{
					$items[$item->getValue()] = $item->getTitle();
				}
				return count($items) ? $items : null;
			}
		}
		return $items;
	}

	/**
	 * @param \Rbs\Elasticsearch\Facet\AggregationValue $aggregationValue
	 * @param array $bucket
	 */
	protected function formatChildren($aggregationValue, array $bucket)
	{
		if ($this->hasChildren())
		{
			foreach ($this->getChildren() as $children)
			{
				$aggregationValue->addAggregationValues($children->formatAggregation($bucket));
			}
		}
	}
}