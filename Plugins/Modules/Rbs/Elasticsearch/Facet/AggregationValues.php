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
	 * @return string
	 */
	public function getRenderingMode()
	{
		return $this->facet->getParameters()->get('renderingMode', false);
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->facet->getTitle();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = [];
		$facet = $this->facet;
		$array['title'] = $facet->getTitle();
		$array['fieldName'] = $facet->getFieldName();
		$array['parameters'] = $facet->getParameters()->toArray();
		$array['hasChildren'] = $facet->hasChildren();
		if ($facet->getParent())
		{
			$array['parent'] = $facet->getParent()->getFieldName();
		}

		foreach ($this->values as $value)
		{
			$array['values'][] = $value->toArray();
		}
		return $array;
	}
}