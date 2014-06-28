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
* @name \Rbs\Elasticsearch\Facet\AggregationValue
*/
class AggregationValue
{
	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var string|null
	 */
	protected $title;

	/**
	 * @var AggregationValues[]
	 */
	protected $aggregationValues = [];

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param string $title
	 */
	function __construct($key, $value = null, $title = null)
	{
		$this->key = $key;
		$this->value = $value;
		$this->title = $title;
	}

	public function addAggregationValues(AggregationValues $aggregationValues)
	{
		$this->aggregationValues[] = $aggregationValues;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param null|string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @param mixed $metric
	 * @return $this
	 */
	public function setValue($metric)
	{
		$this->value = $metric;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return ($this->title) ? $this->title : $this->key;
	}
}