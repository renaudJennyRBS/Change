<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\FacetDefinitionInterface
 */
interface FacetDefinitionInterface
{
	/**
	 * @deprecated
	 */
	const TYPE_TERM = 'term';

	/**
	 * @deprecated
	 */
	const TYPE_RANGE = 'range';

	/**
	 * @deprecated
	 */
	const PARAM_MULTIPLE_CHOICE = 'multipleChoice';

	/**
	 * @deprecated
	 */
	const PARAM_COLLECTION_CODE = 'collectionCode';


	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * Unique facet identifier for URL Query
	 * @return string
	 */
	public function getFieldName();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters();

	/**
	 * Part of index mapping
	 * @return array
	 */
	public function getMapping();

	/**
	 * @return boolean
	 */
	public function hasChildren();

	/**
	 * @return FacetDefinitionInterface[]
	 */
	public function getChildren();

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param array $documentData
	 * @return array
	 */
	public function addIndexData($document, array $documentData);

	/**
	 * @param array $facetFilters
	 * @param array $context
	 * @return \Elastica\Filter\AbstractFilter|null
	 */
	public function getFilterQuery(array $facetFilters, array $context = []);

	/**
	 * @param array $context
	 * @return \Elastica\Aggregation\AbstractAggregation
	 */
	public function getAggregation(array $context = []);

	/**
	 * @param $aggregations
	 * @return \Rbs\Elasticsearch\Facet\AggregationValues
	 */
	public function formatAggregation(array $aggregations);

	/**
	 * @deprecated
	 * @return string
	 */
	public function getFacetType();

	/**
	 * @deprecated
	 * @return boolean
	 */
	public function getShowEmptyItem();
}