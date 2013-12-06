<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\LineInterface;

/**
 * @name \Rbs\Commerce\Cart\CartLine
 */
class CartLine implements LineInterface, \Serializable
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
	 * @var CartLineItem[]
	 */
	protected $items = array();

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @param string|array $key
	 */
	function __construct($key)
	{
		if (is_array($key))
		{
			$this->fromArray($key);
		}
		else
		{
			$this->key = $key;
		}
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		if ($this->serializedData)
		{
			$this->restoreSerializedData($documentManager);
		}
		return $this;
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
	 * @return CartLineItem[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param $codeSKU
	 * @return \Rbs\Commerce\Cart\CartLineItem|null
	 */
	public function getItemByCodeSKU($codeSKU)
	{
		foreach ($this->items as $item)
		{
			if ($item->getCodeSKU() === $codeSKU)
			{
				return $item;
			}
		}
		return null;
	}

	/**
	 * @param \Rbs\Commerce\Cart\CartLineItem $item
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Rbs\Commerce\Cart\CartLineItem
	 */
	public function appendItem($item)
	{
		if ($item instanceof CartLineItem)
		{
			if ($this->getItemByCodeSKU($item->getCodeSKU()))
			{
				throw new \RuntimeException('Duplicate item code SKU: ' . $item->getCodeSKU(), 999999);
			}
			$this->items[] = $item;
			return $item;
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 should be a CartItem', 999999);
		}
	}

	/**
	 * @param string $codeSKU
	 * @return \Rbs\Commerce\Cart\CartLineItem|null
	 */
	public function removeItemByCodeSKU($codeSKU)
	{
		/* @var $result CartLineItem */
		$result = null;
		$items = array();
		foreach ($this->items as $item)
		{
			if ($item->getCodeSKU() === $codeSKU)
			{
				return $result = $item;
			}
			else
			{
				$items[] = $item;
			}
		}
		$this->items = $items;
		return $result;
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
	 * String representation of object
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		$serializedData = array('index' => $this->index,
			'key' => $this->key,
			'quantity' => $this->quantity,
			'designation' => $this->designation,
			'items' => $this->items,
			'options' => $this->options);
		return serialize((new CartStorage())->getSerializableValue($serializedData));
	}

	/**
	 * Constructs the object
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->serializedData = unserialize($serialized);
	}

	protected function restoreSerializedData(\Change\Documents\DocumentManager $documentManager)
	{
		$serializedData = (new CartStorage())->setDocumentManager($documentManager)
			->restoreSerializableValue($this->serializedData);
		$this->serializedData = null;
		$this->index = $serializedData['index'];
		$this->key = $serializedData['key'];
		$this->quantity = $serializedData['quantity'];
		$this->designation = $serializedData['designation'];
		$this->items = $serializedData['items'];
		$this->options = $serializedData['options'];
		foreach ($this->items as $item)
		{
			/* @var $item CartLineItem */
			$item->setDocumentManager($documentManager);
		}
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
							$item = new CartLineItem($itemArray);
							if ($item->getCodeSKU())
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
			'key' => $this->key,
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

	public function __toString()
	{
		return $this->index . ') ' . $this->designation . ' [' . $this->key . ']';
	}

	/**
	 * @return float|null
	 */
	public function getUnitPriceValue()
	{
		return array_reduce($this->items, function ($result, \Rbs\Commerce\Cart\CartLineItem $item)
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
		return array_reduce($this->items, function ($result, \Rbs\Commerce\Cart\CartLineItem $item)
		{
			if ($item->getPriceValue() !== null)
			{
				$tax = array_reduce($item->getCartTaxes(), function ($result, \Rbs\Price\Tax\TaxApplication $cartTax)
				{
					return $result + $cartTax->getValue();
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
			return array_reduce($this->items, function ($result, \Rbs\Commerce\Cart\CartLineItem $item) use ($quantity)
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
			return array_reduce($this->items, function ($result, \Rbs\Commerce\Cart\CartLineItem $item) use ($quantity)
			{
				if ($item->getPriceValue() !== null)
				{
					$tax = array_reduce($item->getCartTaxes(),
						function ($result, \Rbs\Price\Tax\TaxApplication $cartTax) use ($quantity)
						{
							return $result + $cartTax->getValue() * $quantity;
						}, 0.0);
					return $result + ($item->getPriceValue() * $quantity) + $tax;
				}
				return $result;
			});
		}
		return null;
	}
}