<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rbs\Geo\Map;

/**
 * @name \Rbs\Geo\Map\Point
 */
class Point
{

	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var \Rbs\Geo\Address\BaseAddress
	 */
	protected $address;

	/**
	 * @var float
	 */
	protected $longitude;

	/**
	 * @var float
	 */
	protected $latitude;

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * @param \Rbs\Geo\Map\Point|array $data
	 */
	function __construct($data)
	{
		if ($data instanceof Point)
		{
			$data = $data->toArray();
		}

		if (is_array($data))
		{
			$this->fromArray($data);
		}
	}

	/**
	 * @return \Rbs\Geo\Address\BaseAddress|null
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @param \Rbs\Geo\Address\BaseAddress|\Rbs\Geo\Address\AddressInterface|array|null $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		if ($address instanceof \Rbs\Geo\Address\BaseAddress)
		{
			$this->address = $address;
		}
		elseif ($address instanceof \Rbs\Geo\Address\AddressInterface || is_array($address))
		{
			$this->address = new \Rbs\Geo\Address\BaseAddress($address);
		}
		else
		{
			$this->address = null;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->code = $code;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getLatitude()
	{
		return $this->latitude;
	}

	/**
	 * @param float $latitude
	 * @return $this
	 */
	public function setLatitude($latitude)
	{
		$this->latitude = $latitude;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getLongitude()
	{
		return $this->longitude;
	}

	/**
	 * @param float $longitude
	 * @return $this
	 */
	public function setLongitude($longitude)
	{
		$this->longitude = $longitude;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions($options)
	{
		$this->options = is_array($options) ? $options : [];
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	public function fromArray(array $data)
	{
		foreach ($data as $name => $value)
		{
			$callable = [$this, 'set' . ucfirst($name)];
			if (is_callable($callable))
			{
				call_user_func($callable, $value);
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$result = [];
		$result['code'] = $this->code;
		$result['title'] = $this->title;
		$result['longitude'] = $this->longitude;
		$result['latitude'] = $this->latitude;
		$result['address'] = $this->address ? $this->address->toArray() : null;
		$result['options'] = count($this->options) ? $this->options : null;
		return $result;
	}
}