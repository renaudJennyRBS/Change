<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web\Result;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Result\AjaxResult
 */
class AjaxResult extends \Change\Http\Result
{
	/**
	 * @var array
	 */
	protected $array;

	/**
	 * @param array $array
	 */
	function __construct(array $array = null)
	{
		$this->array = $array;
		$this->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$this->getHeaders()->addHeaderLine('Content-Type: application/json');
	}

	/**
	 * @param array $array
	 */
	public function setArray($array)
	{
		$this->array = $array;
	}

	public function hasEntry($name)
	{
		return is_array($this->array) && array_key_exists($name, $this->array);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setEntry($name, $value)
	{
		$this->array[$name] = $value;
		return $this;
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function getEntry($name)
	{
		if ($this->hasEntry($name))
		{
			return $this->array[$name];
		}
		return null;
	}

	/**
	 * @param string $name
	 * @return mixed old value
	 */
	public function removeEntry($name)
	{
		$oldValue = null;
		if ($this->hasEntry($name))
		{
			$oldValue = $this->array[$name];
			unset($this->array[$name]);
		}
		return $oldValue;
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
		return \Zend\Json\Json::encode($this->toArray());
	}
}