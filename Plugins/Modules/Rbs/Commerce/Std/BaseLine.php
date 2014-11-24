<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Std;

use \Rbs\Commerce\Interfaces\LineInterface;

/**
 * @name \Rbs\Commerce\Std\BaseLine
 */
class BaseLine implements LineInterface
{
	/**
	 * @var integer
	 */
	protected $index;

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var integer
	 */
	protected $quantity;

	/**
	 * @var string
	 */
	protected $designation;

	/**
	 * @var \Rbs\Commerce\Std\BaseLineItem[]
	 */
	protected $items = array();

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $taxes = array();

	/**
	 * @var float|null
	 */
	protected $amountWithoutTaxes;

	/**
	 * @var float|null
	 */
	protected $amountWithTaxes;

	/**
	 * @var float|null
	 */
	protected $basedAmountWithoutTaxes;

	/**
	 * @var float|null
	 */
	protected $basedAmountWithTaxes;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param LineInterface|array $data
	 */
	function __construct($data = null)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
		else if ($data instanceof LineInterface)
		{
			$this->fromLine($data);
		}
	}

	/**
	 * @param int $index
	 * @return $this
	 */
	public function setIndex($index)
	{
		$this->index = $index;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getIndex()
	{
		return $this->index;
	}

	/**
	 * @param string $key
	 * @return $this
	 */
	public function setKey($key)
	{
		$this->key = $key;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param integer $quantity
	 * @return $this
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @param string $designation
	 * @return $this
	 */
	public function setDesignation($designation)
	{
		$this->designation = $designation;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDesignation()
	{
		return $this->designation;
	}

	/**
	 * @return \Rbs\Commerce\Std\BaseLineItem[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxes
	 * @return $this
	 */
	public function setTaxes($taxes)
	{
		$this->taxes = array();
		if (is_array($taxes))
		{
			foreach ($taxes as $tax)
			{
				$this->appendTax($tax);
			}
		}
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTaxes()
	{
		return $this->taxes;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication $tax
	 */
	public function appendTax(\Rbs\Price\Tax\TaxApplication $tax)
	{
		$this->taxes[] = $tax;
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
	 * @param float|null $amountWithTaxes
	 * @return $this
	 */
	public function setAmountWithTaxes($amountWithTaxes)
	{
		$this->amountWithTaxes = $amountWithTaxes;
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getAmountWithTaxes()
	{
		return $this->amountWithTaxes;
	}

	/**
	 * @param float|null $amountWithoutTaxes
	 * @return $this
	 */
	public function setAmountWithoutTaxes($amountWithoutTaxes)
	{
		$this->amountWithoutTaxes = $amountWithoutTaxes;
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getAmountWithoutTaxes()
	{
		return $this->amountWithoutTaxes;
	}

	/**
	 * @param float|null $basedAmountWithoutTaxes
	 * @return $this
	 */
	public function setBasedAmountWithoutTaxes($basedAmountWithoutTaxes)
	{
		$this->basedAmountWithoutTaxes = $basedAmountWithoutTaxes;
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getBasedAmountWithoutTaxes()
	{
		return $this->basedAmountWithoutTaxes;
	}


	/**
	 * @param float|null $basedAmountWithTaxes
	 * @return $this
	 */
	public function setBasedAmountWithTaxes($basedAmountWithTaxes)
	{
		$this->basedAmountWithTaxes = $basedAmountWithTaxes;
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getBasedAmountWithTaxes()
	{
		return $this->basedAmountWithTaxes;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		$this->options = null;
		$this->items = array();
		$this->taxes = array();
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'index':
					$this->setIndex(intval($value));
					break;
				case 'key':
					$this->setKey(strval($value));
					break;
				case 'quantity':
					$this->setQuantity(intval($value));
					break;
				case 'designation':
					$this->setDesignation($value);
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
				case 'items':
					if (is_array($value))
					{
						foreach ($value as $itemArray)
						{
							$item = $this->getNewItemFromArray($itemArray);
							if($item instanceof \Rbs\Commerce\Std\BaseLineItem)
							{
								$this->appendItem($item);
							}
						}
					}
					break;
				case 'taxes':
					if (is_array($value))
					{
						foreach ($value as $tax)
						{
							if (is_array($tax) && isset($tax['taxCode']) && isset($tax['category'])  && isset($tax['zone']))
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
				case 'amount':
				case 'amountWithoutTaxes':
					$this->setAmountWithoutTaxes($value);
					break;
				case 'amountWithTaxes':
					$this->setAmountWithTaxes($value);
					break;
				case 'basedAmount':
				case 'basedAmountWithoutTaxes':
					$this->setBasedAmountWithoutTaxes($value);
					break;
				case 'basedAmountWithTaxes':
					$this->setBasedAmountWithTaxes($value);
					break;
			}

			if ($this->quantity === null)
			{
				$this->quantity = 1;
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
		$array = [
			'index' => $this->index,
			'key' => $this->key,
			'quantity' => $this->quantity,
			'designation' => $this->designation,
			'items' => array(),
			'taxes' => array(),
			'amountWithoutTaxes' => $this->amountWithoutTaxes,
			'amountWithTaxes' => $this->amountWithTaxes,
			'basedAmountWithoutTaxes' => $this->basedAmountWithoutTaxes,
			'basedAmountWithTaxes' => $this->basedAmountWithTaxes,
			'options' => count($options) ? $options : null
		];
		foreach ($this->items as $item)
		{
			$array['items'][] = $item->toArray();
		}
		foreach ($this->taxes as $tax)
		{
			$array['taxes'][] = $tax->toArray();
		}
		return $array;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineInterface $line
	 * @return $this
	 */
	public function fromLine(LineInterface $line)
	{
		$this->setIndex($line->getIndex());
		$this->setKey($line->getKey());
		$this->setQuantity($line->getQuantity());
		$this->setDesignation($line->getDesignation());

		$this->options = null;
		foreach($line->getOptions() as $name => $option)
		{
			$this->getOptions()->set($name, $option);
		}
		$this->items = [];
		foreach($line->getItems() as $item)
		{
			$this->items[] = $this->getNewItemFromLineItem($item);
		}

		$this->taxes = [];
		$taxes = $line->getTaxes();
		foreach($taxes as $tax)
		{
			$this->appendTax($tax);
		}

		$this->setAmountWithTaxes($line->getAmountWithTaxes());
		$this->setAmountWithoutTaxes($line->getAmountWithoutTaxes());

		$this->setBasedAmountWithTaxes($line->getBasedAmountWithTaxes());
		$this->setBasedAmountWithoutTaxes($line->getBasedAmountWithoutTaxes());
		return $this;
	}

	/**
	 * @param array $itemArray
	 * @return \Rbs\Commerce\Std\BaseLineItem
	 */
	protected function getNewItemFromArray($itemArray)
	{
		return new BaseLineItem($itemArray);
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineItemInterface $lineItem
	 * @return \Rbs\Commerce\Std\BaseLineItem
	 */
	protected function getNewItemFromLineItem($lineItem)
	{
		return new BaseLineItem($lineItem);
	}

	/**
	 * @param \Rbs\Commerce\Std\BaseLineItem $item
	 * @return \Rbs\Commerce\Std\BaseLineItem
	 */
	public function appendItem($item)
	{
		$this->items[] = $item;
		return $item;
	}



	/**
	 * @return float|null
	 */
	public function getUnitAmountWithoutTaxes()
	{
		$value = $this->getAmountWithoutTaxes();
		if ($value !== null && $this->getQuantity())
		{
			return $value / floatval($this->getQuantity());
		}
		return null;
	}

	/**
	 * @return float|null
	 */
	public function getUnitAmountWithTaxes()
	{
		$value = $this->getAmountWithTaxes();
		if ($value !== null && $this->getQuantity())
		{
			return $value / floatval($this->getQuantity());
		}
		return null;
	}


	/**
	 * @return float|null
	 */
	public function getUnitBasedAmountWithoutTaxes()
	{
		$value = $this->getBasedAmountWithoutTaxes();
		if ($value !== null && $this->getQuantity())
		{
			return $value / floatval($this->getQuantity());
		}
		return null;
	}

	/**
	 * @return float|null
	 */
	public function getUnitBasedAmountWithTaxes()
	{
		$value = $this->getBasedAmountWithTaxes();
		if ($value !== null && $this->getQuantity())
		{
			return $value / floatval($this->getQuantity());
		}
		return null;
	}

	/**
	 * @deprecated
	 * @param $amount
	 * @return \Rbs\Commerce\Std\BaseLine
	 */
	public function setAmount($amount)
	{
		return $this->setAmountWithoutTaxes($amount);
	}

	/**
	 * @deprecated
	 */
	public function getAmount()
	{
		return $this->getAmountWithoutTaxes();
	}

	/**
	 * @deprecated
	 * @param $basedAmount
	 * @return \Rbs\Commerce\Std\BaseLine
	 */
	public function setBasedAmount($basedAmount)
	{
		return $this->setBasedAmountWithoutTaxes($basedAmount);
	}

	/**
	 * @deprecated
	 */
	public function getBasedAmount()
	{
		return $this->getBasedAmountWithoutTaxes();
	}

	/**
	 * @deprecated
	 */
	public function getUnitAmount()
	{
		return $this->getUnitAmountWithoutTaxes();
	}

	/**
	 * @deprecated
	 */
	public function getUnitBasedAmount()
	{
		return $this->getUnitBasedAmountWithoutTaxes();
	}
}