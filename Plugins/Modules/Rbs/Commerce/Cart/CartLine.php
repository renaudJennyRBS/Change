<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\LineInterface;

/**
 * @name \Rbs\Commerce\Cart\CartLine
 */
class CartLine extends \Rbs\Commerce\Std\BaseLine implements LineInterface, \Serializable
{
	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @param string|array|LineInterface $key
	 */
	function __construct($key)
	{
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
			'priceValue' => $this->priceValue,
			'priceValueWithTax' => $this->priceValueWithTax,
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
		$this->taxes = $serializedData['taxes'];
		$this->priceValue = $serializedData['priceValue'];
		$this->priceValueWithTax = $serializedData['priceValueWithTax'];
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
		parent::fromArray($array);
		if(array_key_exists('key', $array))
		{
			$this->setKey(strval($array['key']));
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = parent::toArray();
		$array['key'] = $this->key;
		return $array;
	}

	/**
	 * @param array $itemArray
	 * @return \Rbs\Commerce\Cart\CartLineItem | null
	 */
	protected function getNewItemFromArray($itemArray)
	{
		$item = new CartLineItem($itemArray);
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
		return new CartLineItem($lineItem);
	}

	public function __toString()
	{
		return $this->index . ') ' . $this->designation . ' [' . $this->key . ']';
	}
}