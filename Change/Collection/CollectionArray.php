<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
			$this->addItem($value, $label);
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

	/**
	 * @param string $value
	 * @param string|array|\Change\I18n\I18nString $label
	 * @return $this
	 */
	public function addItem($value, $label)
	{
		foreach ($this->items as $key => $item)
		{
			if ($item->getValue() === $value)
			{
				$this->items[$key] = new BaseItem($value, $label);
				return $this;
			}
		}
		$this->items[] = new BaseItem($value, $label);
		return $this;
	}

	/**
	 * @param $value
	 * @return $this
	 */
	public function removeItemByValue($value)
	{
		foreach ($this->items as $index => $item)
		{
			if ($item->getValue() === $value)
			{
				unset($this->items[$index]);
			}
		}
		$this->items = array_values($this->items);
		return $this;
	}
}