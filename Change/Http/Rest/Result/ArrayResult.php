<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\ArrayResult
 */
class ArrayResult extends \Change\Http\Result
{
	/**
	 * @var array
	 */
	protected $array;

	/**
	 * @param array $array
	 */
	public function setArray($array)
	{
		$this->array = $array;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		if (is_array($this->array))
		{
			return $this->array;
		}
		return array();
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return json_encode($this->toArray());
	}
}