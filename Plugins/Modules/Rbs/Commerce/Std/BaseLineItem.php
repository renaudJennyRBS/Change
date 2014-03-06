<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Std;

use \Rbs\Commerce\Interfaces\LineItemInterface;

/**
 * @name \Rbs\Commerce\Std\BaseLineItem
 */
class BaseLineItem implements LineItemInterface
{
	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var integer|null
	 */
	protected $reservationQuantity;

	/**
	 * @var \Rbs\Commerce\Std\BasePrice|null
	 */
	protected $price;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param LineItemInterface|array $data
	 */
	function __construct($data)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
		else if ($data instanceof LineItemInterface)
		{
			$this->fromLineItem($data);
		}
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCodeSKU($code)
	{
		$this->codeSKU = $code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCodeSKU()
	{
		return $this->codeSKU;
	}

	/**
	 * @param integer|null $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity)
	{
		$this->reservationQuantity = ($reservationQuantity === null) ? $reservationQuantity : intval($reservationQuantity);
		return $this;
	}

	/**
	 * @return integer|null
	 */
	public function getReservationQuantity()
	{
		return $this->reservationQuantity;
	}

	/**
	 * @param \Rbs\Price\PriceInterface|array|float|null $price
	 * @return $this
	 */
	public function setPrice($price)
	{
		$this->price = new \Rbs\Commerce\Std\BasePrice($price);
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Std\BasePrice|null
	 */
	public function getPrice()
	{
		return $this->price;
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
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'codeSKU':
					$this->codeSKU = strval($value);
					break;
				case 'reservationQuantity':
					$this->reservationQuantity = intval($value);
					break;
				case 'price':
					$this->setPrice($value);
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

			if ($this->reservationQuantity === null && $this->codeSKU)
			{
				$this->reservationQuantity = 1;
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$options = $this->getOptions()->toArray();
		$array = array(
			'codeSKU' => $this->codeSKU,
			'reservationQuantity' => $this->reservationQuantity,
			'options' => count($options) ? $options : null);

		$price = $this->getPrice();
		if ($price) {
			$array['price'] = $price->toArray();
		}
		return $array;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineItemInterface $item
	 * @return $this
	 */
	public function fromLineItem(LineItemInterface $item)
	{
		$this->setCodeSKU($item->getCodeSKU());
		$this->setReservationQuantity($item->getReservationQuantity());
		if (($price = $item->getPrice()) !== null)
		{
			$this->setPrice($price);
		}
		$this->options = null;
		foreach($item->getOptions() as $name => $option)
		{
			$this->getOptions()->set($name, $option);
		}
		return $this;
	}
}