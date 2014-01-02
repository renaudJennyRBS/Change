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
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'index':
					$this->setIndex(intval($value));
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
		$array = array('index' => $this->index,
			'quantity' => $this->quantity,
			'designation' => $this->designation,
			'items' => array(),
			'options' => $this->getOptions()->toArray());
		foreach ($this->items as $item)
		{
			$array['items'][] = $item->toArray();
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
		$this->setQuantity($line->getQuantity());
		$this->setDesignation($line->getDesignation());
		$this->options = null;
		foreach($line->getOptions() as $name => $option)
		{
			$this->getOptions()->set($name, $option);
		}
		$this->items = array();
		foreach($line->getItems() as $item)
		{
			$this->items[] = $this->getNewItemFromLineItem($item);
		}

		return $this;
	}

	/**
	 * @param array $itemArray
	 * @return \Rbs\Commerce\Std\BaseLineItem|null
	 */
	protected function getNewItemFromArray($itemArray)
	{
		return new BaseLineItem($itemArray);
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineItemInterface $lineItem
	 * @return \Rbs\Commerce\Std\BaseLineItem|null
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
		return array_reduce($this->items, function ($result, \Rbs\Commerce\Std\BaseLineItem $item)
		{
			if ($item->getPriceValue() !== null)
			{
				return $result + $item->getPriceValue();
			}
			return $result;
		});
	}

	/**
	 * @return float|null
	 */
	public function getUnitPriceValueWithTax()
	{
		return array_reduce($this->items, function ($result, \Rbs\Commerce\Std\BaseLineItem $item)
		{
			if ($item->getPriceValue() !== null)
			{
				$tax = array_reduce($item->getTaxes(), function ($result, \Rbs\Price\Tax\TaxApplication $tax)
				{
					return $result + $tax->getValue();
				}, 0.0);
				return $result + $item->getPriceValue() + $tax;
			}
			return $result;
		});
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		$quantity = $this->getQuantity();
		if ($quantity)
		{
			return array_reduce($this->items, function ($result, \Rbs\Commerce\Std\BaseLineItem $item) use ($quantity)
			{
				if ($item->getPriceValue() !== null)
				{
					return $result + ($item->getPriceValue() * $quantity);
				}
				return $result;
			});
		}
		return null;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax()
	{
		$quantity = $this->getQuantity();
		if ($quantity)
		{
			return array_reduce($this->items, function ($result, \Rbs\Commerce\Std\BaseLineIte $item) use ($quantity)
			{
				if ($item->getPriceValue() !== null)
				{
					$tax = array_reduce($item->getTaxes(),
						function ($result, \Rbs\Price\Tax\TaxApplication $tax) use ($quantity)
						{
							return $result + $tax->getValue() * $quantity;
						}, 0.0);
					return $result + ($item->getPriceValue() * $quantity) + $tax;
				}
				return $result;
			});
		}
		return null;
	}
}