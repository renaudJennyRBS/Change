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
* @name \Rbs\Elasticsearch\Facet\AggregationValues
*/
class AggregationValues
{
	/**
	 * @var FacetDefinitionInterface
	 */
	protected $facet;

	/**
	 * @var AggregationValue[]
	 */
	protected $values = [];

	/**
	 * @param FacetDefinitionInterface $facet
	 */
	function __construct(FacetDefinitionInterface $facet)
	{
		$this->facet = $facet;
	}

	/**
	 * @param $aggregationValue
	 * @return $this
	 */
	public function addValue(AggregationValue $aggregationValue)
	{
		$this->values[] = $aggregationValue;
		return $this;
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\FacetDefinitionInterface
	 */
	public function getFacet()
	{
		return $this->facet;
	}

	/**
	 * @return \Rbs\Elasticsearch\Facet\AggregationValue[]
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->facet->getFieldName();
	}

	/**
	 * @return boolean
	 */
	public function getMultipleChoice()
	{
		return $this->facet->getParameters()->get('multipleChoice', false);
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->facet->getTitle();
	}
}