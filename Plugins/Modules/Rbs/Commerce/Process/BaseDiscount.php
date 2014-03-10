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
 * @name \Rbs\Commerce\Process\BaseDiscount
 */
class BaseDiscount implements \Rbs\Commerce\Process\DiscountInterface
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
	 * @var \Rbs\Commerce\Std\BasePrice|null
	 */
	protected $price;

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $taxes = [];

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param array|\Rbs\Commerce\Process\DiscountInterface|null $data
	 */
	public function __construct($data = null)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
		elseif ($data instanceof \Rbs\Commerce\Process\DiscountInterface)
		{
			$this->fromDiscount($data);
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
	 * @param \Rbs\Price\Tax\TaxApplication $tax
	 */
	public function appendTax(\Rbs\Price\Tax\TaxApplication $tax)
	{
		$this->taxes[] = $tax;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTaxes()
	{
		return $this->taxes;
	}

	/**
	 * @return float|null
	 */
	public function getAmount()
	{
		if ($this->price)
		{
			$value = $this->price->getValue();
			if ($value && $this->price->isWithTax())
			{
				foreach ($this->taxes as $tax)
				{
					$value -= $tax->getValue();
				}
			}
			return $value;
		}
		return $this->getOptions()->get('amount');
	}

	/**
	 * @return float|null
	 */
	public function getAmountWithTaxes()
	{
		if ($this->price)
		{
			$value = $this->price->getValue();
			if ($value && !$this->price->isWithTax())
			{
				foreach ($this->taxes as $tax)
				{
					$value += $tax->getValue();
				}
			}
			return $value;
		}
		return $this->getOptions()->get('amountWithTaxes');
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
	 * @param \Rbs\Commerce\Process\DiscountInterface $discount
	 * @return $this
	 */
	public function fromDiscount($discount)
	{
		$this->fromArray($discount->toArray());
		return $this;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		$this->options = null;
		$this->taxes = [];

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
					if (is_array($value))
					{
						foreach ($value as $lineKey)
						{
							$this->lineKeys[] = strval($lineKey);
						}
					}
					break;
				case 'price':
					$this->setPrice($value);
					break;
				case 'amount':
					$this->getOptions()->set('amount', $value);
					break;
				case 'amountWithTaxes':
					$this->getOptions()->set('amountWithTaxes', $value);
					break;
				case 'taxes':
					if (is_array($value))
					{
						foreach ($value as $tax)
						{
							if (is_array($tax) && isset($tax['taxCode']) && isset($tax['category']) && isset($tax['zone']))
							{
								$taxApplication = new \Rbs\Price\Tax\TaxApplication($tax['taxCode'], $tax['category'], $tax['zone']);
								if (isset($tax['rate']))
								{
									$taxApplication->setRate($tax['rate']);
								}
								if (isset($tax['value']))
								{
									$taxApplication->setValue($tax['value']);
								}
								$this->appendTax($taxApplication);
							}
						}
					}
					break;
				case 'options':
					if (is_array($value))
					{
						$options = $this->getOptions();
						foreach ($value as $optName => $optValue)
						{
							$options->set($optName, $optValue);
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
			'taxes' => [],
			'options' => $this->getOptions()->toArray()
		);

		$price = $this->getPrice();
		if ($price)
		{
			$array['price'] = $price->toArray();
		}

		foreach ($this->taxes as $tax)
		{
			$array['taxes'][] = $tax->toArray();
		}
		return $array;
	}
}