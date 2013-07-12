<?php

namespace Change\Collection;


class CollectionArray implements CollectionInterface
{
	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var BaseItem[]
	 */
	protected $items = array();

	/**
	 * @param string $code
	 * @param array $items
	 */
	function __construct($code, array $items)
	{
		$this->code = $code;
		foreach ($items as $value => $label)
		{
			$this->items[] = new BaseItem($value, $label);
		}
	}

	/**
	 * @return \Change\Collection\ItemInterface[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param mixed $value
	 * @return \Change\Collection\ItemInterface|null
	 */
	public function getItemByValue($value)
	{
		foreach ($this->items as $item)
		{
			if ($item->getValue() === $value)
			{
				return $item;
			}
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}
}