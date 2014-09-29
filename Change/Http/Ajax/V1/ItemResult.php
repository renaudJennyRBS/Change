<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Ajax\V1;

/**
 * @name \Change\Http\Ajax\V1\ItemResult
 */
class ItemResult extends \Change\Http\Result
{

	/**
	 * @var string
	 */
	protected $name = null;

	/**
	 * @var array
	 */
	protected $dataSets = [];

	/**
	 * @param string $name
	 * @param array $items
	 */
	function __construct($name = null, array $items = null)
	{
		parent::__construct(\Zend\Http\Response::STATUS_CODE_200);
		$this->setName($name);
		if (is_array($items))
		{
			$this->setDataSets($items);
		}
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param array $dataSets
	 * @return $this
	 */
	public function setDataSets(array $dataSets = [])
	{
		$this->dataSets = $dataSets;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getDataSets()
	{
		return $this->dataSets;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return ['name' => $this->name, 'dataSets' => $this->dataSets];
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return json_encode($this->toArray());
	}
}