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
	 * @return \Rbs\Geo\Address\BaseAddress
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @param \Rbs\Geo\Address\BaseAddress $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		$this->address = $address;
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
		$this->options = $options;
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

	public function toArray()
	{
		$result = [];
		$result['code'] = $this->code;
		$result['title'] = $this->title;
		$result['longitude'] = $this->longitude;
		$result['latitude'] = $this->latitude;
		if ($this->address != null)
		{
			$result['address'] = $this->address->toArray();
		}
		$result['options'] = $this->options;
		return $result;
	}
}