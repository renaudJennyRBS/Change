<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Process;

/**
 * @name \Rbs\Commerce\Process\BaseCoupon
 */
class BaseCoupon implements \Rbs\Commerce\Process\CouponInterface
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
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param array $data
	 */
	public function __construct($data = null)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
		elseif($data instanceof \Rbs\Commerce\Process\CouponInterface)
		{
			$this->fromArray($data->toArray());
		}
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
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
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
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
		if ($this->options === null)
		{
			$this->options = new \Zend\Stdlib\Parameters();
		}
		return $this->options;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		$this->options = null;
		$this->code = null;
		$this->title = null;
		foreach ($array as $name => $value)
		{
			if ($value === null)
			{
				continue;
			}
			switch ($name)
			{
				case 'code':
					$this->setCode(strval($value));
					break;
				case 'title':
					$this->setTitle(strval($value));
					break;
				case 'options':
					if (is_array($value))
					{
						foreach ($value as $optName => $optValue)
						{
							$this->getOptions()->set($optName, $optValue);
						}
					}
					break;
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'code' => $this->code,
			'title' => $this->title,
			'options' => $this->options ? $this->options->toArray() : null
		);
		return $array;
	}
}