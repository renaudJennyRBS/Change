<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\LineInterface;

/**
 * @name \Rbs\Commerce\Cart\CartLine
 * @method \Rbs\Commerce\Cart\CartLineItem[] getItems()
 */
class CartLine extends \Rbs\Commerce\Std\BaseLine implements LineInterface, \Serializable
{
	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @var \Rbs\Commerce\Cart\CartManager
	 */
	protected $cartManager;

	/**
	 * @param string|array|LineInterface $key
	 * @param CartManager $cartManager
	 */
	function __construct($key, \Rbs\Commerce\Cart\CartManager $cartManager)
	{
		$this->cartManager = $cartManager;

		if (is_array($key))
		{
			$this->fromArray($key);
		}
		else if ($key instanceof LineInterface)
		{
			$this->fromLine($key);
		}
		else
		{
			$this->setKey($key);
		}
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartManager
	 */
	protected function getCartManager()
	{
		return $this->cartManager;
	}

	/**
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 * @return $this
	 */
	public function setCartManager(\Rbs\Commerce\Cart\CartManager $cartManager)
	{
		$this->cartManager = $cartManager;
		if ($this->serializedData)
		{
			$this->restoreSerializedData();
		}
		else
		{
			foreach ($this->getItems() as $item)
			{
				$item->setCartManager($cartManager);
			}
		}
		return $this;
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
			'taxes' => $this->taxes,
			'amount' => $this->amount,
			'amountWithTaxes' => $this->amountWithTaxes,
			'options' => $this->options);
		return serialize($this->getCartManager()->getSerializableValue($serializedData));
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

	/**
	 * @return array
	 */
	protected function restoreSerializedData()
	{
		$serializedData = $this->getCartManager()->restoreSerializableValue($this->serializedData);

		$this->serializedData = null;
		$this->index = $serializedData['index'];
		$this->key = $serializedData['key'];
		$this->quantity = $serializedData['quantity'];
		$this->designation = $serializedData['designation'];
		$this->items = $serializedData['items'];
		$this->taxes = $serializedData['taxes'];
		$this->amount = $serializedData['amount'];
		$this->amountWithTaxes = $serializedData['amountWithTaxes'];
		$this->options = $serializedData['options'];
		foreach ($this->items as $item)
		{
			/* @var $item CartLineItem */
			$item->setCartManager($this->getCartManager());
		}

		return $serializedData;
	}

	/**
	 * @param array $itemArray
	 * @return \Rbs\Commerce\Cart\CartLineItem | null
	 */
	protected function getNewItemFromArray($itemArray)
	{
		$item = new CartLineItem($itemArray, $this->getCartManager());
		if ($item->getCodeSKU())
		{
			return $item;
		}
		return null;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineItemInterface $lineItem
	 * @return \Rbs\Commerce\Cart\CartLineItem | null
	 */
	protected function getNewItemFromLineItem($lineItem)
	{
		return new CartLineItem($lineItem, $this->getCartManager());
	}

	public function __toString()
	{
		return $this->index . ') ' . $this->designation . ' [' . $this->key . ']';
	}
}