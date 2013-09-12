<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\CartLine as CartLineInterfaces;

/**
 * @name \Rbs\Commerce\Cart\CartLine
 */
class CartLine implements CartLineInterfaces
{
	/**
	 * @var integer
	 */
	protected $number;

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
	 * @var CartItem[]
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
	 * @param string $key
	 */
	function __construct($key)
	{
		$this->key = $key;
	}

	/**
	 * @param Cart $cart
	 * @return $this
	 */
	public function setCart(Cart $cart)
	{
		if ($this->serializedData)
		{
			$this->restoreSerializedData($cart);
		}
		return $this;
	}

	/**
	 * @param int $number
	 * @return $this
	 */
	public function setNumber($number)
	{
		$this->number = $number;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getNumber()
	{
		return $this->number;
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
	 * @return CartItem[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param $codeSKU
	 * @return \Rbs\Commerce\Cart\CartItem|null
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
	 * @param \Rbs\Commerce\Cart\CartItem $item
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Rbs\Commerce\Cart\CartItem
	 */
	public function appendItem($item)
	{
		if ($item instanceof CartItem)
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
	 * @return \Rbs\Commerce\Cart\CartItem|null
	 */
	public function removeItemByCodeSKU($codeSKU)
	{
		/* @var $result CartItem */
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
		$serializedData = array('number' => $this->number,
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

	protected function restoreSerializedData(Cart $cart)
	{
		$serializedData = (new CartStorage())->restoreSerializableValue($this->serializedData, $cart->getCommerceServices());
		$this->serializedData = null;
		$this->number = $serializedData['number'];
		$this->key = $serializedData['key'];
		$this->quantity = $serializedData['quantity'];
		$this->designation = $serializedData['designation'];
		$this->items = $serializedData['items'];
		$this->options = $serializedData['options'];
		foreach ($this->items as $item)
		{
			/* @var $item CartItem */
			$item->setCart($cart);
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array('number' => $this->number,
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
		return $this->number . ') ' . $this->designation . ' [' . $this->key . ']';
	}

	/**
	 * @return float|null
	 */
	public function getUnitPriceValue()
	{
		return array_reduce($this->items, function ($result, \Rbs\Commerce\Cart\CartItem $item)
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
		return array_reduce($this->items, function ($result, \Rbs\Commerce\Cart\CartItem $item)
		{
			if ($item->getPriceValue() !== null)
			{
				$tax = array_reduce($item->getCartTaxes(), function ($result, \Rbs\Commerce\Cart\CartTax $cartTax)
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
			return array_reduce($this->items, function ($result, \Rbs\Commerce\Cart\CartItem $item) use ($quantity)
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
			return array_reduce($this->items, function ($result, \Rbs\Commerce\Cart\CartItem $item) use ($quantity)
			{
				if ($item->getPriceValue() !== null)
				{
					$tax = array_reduce($item->getCartTaxes(), function ($result, \Rbs\Commerce\Cart\CartTax $cartTax) use ($quantity)
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