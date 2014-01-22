<?php
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
	protected $priceValue;

	/**
	 * @var float|null
	 */
	protected $priceValueWithTax;

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
	 * @param float|null $priceValueWithTax
	 * @return $this
	 */
	public function setPriceValueWithTax($priceValueWithTax)
	{
		$this->priceValueWithTax = $priceValueWithTax;
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax()
	{
		return $this->priceValueWithTax;
	}

	/**
	 * @param float|null $priceValue
	 * @return $this
	 */
	public function setPriceValue($priceValue)
	{
		$this->priceValue = $priceValue;
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		return $this->priceValue;
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
				case 'index':
					$this->setIndex(intval($value));
					break;
				case 'key':
					$this->setKey($value);
					break;
				case 'quantity':
					$this->setQuantity(intval($value));
					break;
				case 'designation':
					$this->setDesignation($value);
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
				case 'items':
					$this->items = array();
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
				case 'priceValue':
					$this->setPriceValue($value);
					break;
				case 'priceValueWithTax':
					$this->setPriceValueWithTax($value);
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
		$array = [
			'index' => $this->index,
			'key' => $this->key,
			'quantity' => $this->quantity,
			'designation' => $this->designation,
			'items' => array(),
			'taxes' => array(),
			'priceValue' => $this->priceValue,
			'priceValueWithTax' => $this->priceValueWithTax,
			'options' => $this->getOptions()->toArray()
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

		$this->setPriceValueWithTax($line->getPriceValueWithTax());
		$this->setPriceValue($line->getPriceValue());
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
	public function getUnitPriceValue()
	{
		$value = $this->getPriceValue();
		if ($value !== null && $this->getQuantity())
		{
			return $value / floatval($this->getQuantity());
		}
		return null;
	}

	/**
	 * @return float|null
	 */
	public function getUnitPriceValueWithTax()
	{
		$value = $this->getPriceValueWithTax();
		if ($value !== null && $this->getQuantity())
		{
			return $value / floatval($this->getQuantity());
		}
		return null;
	}
}