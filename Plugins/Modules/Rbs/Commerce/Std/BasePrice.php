<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Std;


/**
* @name \Rbs\Commerce\Std\BasePrice
*/
class BasePrice implements \Rbs\Price\PriceInterface
{

	/**
	 * @var float|null
	 */
	protected $value;

	/**
	 * @var float|null
	 */
	protected $basePriceValue;

	/**
	 * @var array
	 */
	protected $taxCategories = array();

	/**
	 * @var boolean
	 */
	protected $withTax = false;

	/**
	 * @param \Rbs\Price\PriceInterface|array|float $price
	 */
	function __construct($price)
	{
		if ($price instanceof \Rbs\Price\PriceInterface)
		{
			$this->fromPrice($price);
		}
		elseif (is_array($price))
		{
			$this->fromArray($price);
		}
		elseif (is_numeric($price))
		{
			$this->setValue($price);
		}
	}

	/**
	 * @param \Rbs\Price\PriceInterface $price
	 * @return $this
	 */
	public function fromPrice(\Rbs\Price\PriceInterface $price)
	{
		$this->value = $price->getValue();
		$this->withTax = $price->isWithTax();
		if ($price->isDiscount())
		{
			$this->basePriceValue = $price->getBasePriceValue();
		}
		else
		{
			$this->basePriceValue = null;
		}
		$this->setTaxCategories($price->getTaxCategories());
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
				case 'value':
					$this->setValue($value);
					break;
				case 'withTax':
					$this->setWithTax($value);
					break;
				case 'basePriceValue':
					$this->basePriceValue = $value === null ? $value : floatval($value);
					break;
				case 'taxCategories':
					$this->setTaxCategories($value);
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
		return get_object_vars($this);
	}

	/**
	 * @param float|null $value
	 * @return $this
	 */
	public function setValue($value)
	{
		$this->value = $value === null ? $value : floatval($value);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return boolean
	 */
	public function isDiscount()
	{
		return $this->basePriceValue !== null;
	}

	/**
	 * @param float|null $basePriceValue
	 * @return $this
	 */
	public function setBasePriceValue($basePriceValue)
	{
		$this->basePriceValue = $basePriceValue === null ? $basePriceValue : floatval($basePriceValue);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getBasePriceValue()
	{
		return $this->basePriceValue;
	}

	/**
	 * @param array $taxCategories
	 * @return $this
	 */
	public function setTaxCategories($taxCategories)
	{
		$this->taxCategories = is_array($taxCategories) ? $taxCategories : array();
		return $this;
	}

	/**
	 * @return array<taxCode => category>
	 */
	public function getTaxCategories()
	{
		return $this->taxCategories;
	}

	/**
	 * @param boolean $withTax
	 * @return $this
	 */
	public function setWithTax($withTax)
	{
		$this->withTax = ($withTax == true);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isWithTax()
	{
		return $this->withTax;
	}
}