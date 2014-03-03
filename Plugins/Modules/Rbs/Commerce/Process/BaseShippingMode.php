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
 * @name \Rbs\Commerce\Process\BaseShippingMode
 */
class BaseShippingMode implements \Rbs\Commerce\Process\ShippingModeInterface
{
	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var integer[]
	 */
	protected $lineKeys;

	/**
	 * @var \Rbs\Geo\Address\AddressInterface
	 */
	protected $address;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param array|\Rbs\Commerce\Process\ShippingModeInterface|null $data
	 */
	public function __construct($data = null)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
		elseif ($data instanceof \Rbs\Commerce\Process\ShippingModeInterface)
		{
			$this->fromShippingMode($data);
		}
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
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
	 * @param \integer[] $lineKeys
	 * @return $this
	 */
	public function setLineKeys($lineKeys)
	{
		$this->lineKeys = $lineKeys;
		return $this;
	}

	/**
	 * @return \integer[]
	 */
	public function getLineKeys()
	{
		return $this->lineKeys;
	}

	/**
	 * @param \Zend\Stdlib\Parameters $options
	 * @return $this
	 */
	public function setOptions($options)
	{
		$this->options = $options;
		return $this;
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
	 * @param \Rbs\Geo\Address\BaseAddress $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		$this->address = $address;
		return $this;
	}

	/**
	 * @return \Rbs\Geo\Address\BaseAddress
	 */
	public function getAddress()
	{
		return $this->address;
	}


	/**
	 * @param \Rbs\Commerce\Process\ShippingModeInterface $shippingMode
	 * @return $this
	 */
	public function fromShippingMode($shippingMode)
	{
		$this->fromArray($shippingMode->toArray());
		return $this;
	}
	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'id':
					$this->setId(intval($value));
					break;

				case 'title':
					$this->setTitle(strval($value));
					break;

				case 'lineKeys':
					$this->lineKeys = array();
					foreach ($value as $lineKey)
					{
						$this->lineKeys[] = strval($lineKey);
					}
					break;

				case 'address':
					if (!is_array($value))
					{
						$address = null;
					}
					else
					{
						$address = new \Rbs\Geo\Address\BaseAddress($value);
					}
					$this->address = $address;
					break;

				case 'options':
					$this->options = null;
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
			'id' => $this->id,
			'title' => $this->title,
			'lineKeys' => $this->lineKeys,
			'address' => $this->address ? $this->address->toArray() : null,
			'options' => $this->getOptions()->toArray()
		);
		return $array;
	}
} 