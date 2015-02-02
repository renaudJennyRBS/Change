<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Layout;

/**
 * @package Change\Presentation\Layout
 * @name \Change\Presentation\Layout\Layout
 */
class Layout
{
	/**
	 * @var Item[]
	 */
	protected $items;

	/**
	 * @param array $array
	 */
	function __construct(array $array = null)
	{
		if ($array !== null && count($array))
		{
			$this->items = $this->fromArray($array);
		}
		else
		{
			$this->items = [];
		}
	}

	/**
	 * @return Item[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param array $items
	 */
	public function setItems(array $items)
	{
		$this->items = [];
		foreach ($items as $value)
		{
			if ($value instanceof Item)
			{
				$this->items[$value->getId()] = $value;
			}
		}
	}

	/**
	 * @param Item $item
	 */
	public function addItem(Item $item)
	{
		if (!is_array($this->items))
		{
			$this->items = [];
		}
		$this->items[$item->getId()] = $item;
	}

	/**
	 * @param string $type
	 * @return Item[]
	 */
	public function getItemsByType($type)
	{
		$result = [];
		if (count($this->items))
		{
			foreach ($this->items as $item)
			{
				/* @var $item Item */
				$result = array_merge($result, $item->getItemsByType($type));
			}
		}
		return $result;
	}

	/**
	 * @param string $id
	 * @return Item|null
	 */
	public function getById($id)
	{
		if (count($this->items))
		{
			foreach ($this->items as $item)
			{
				$result = $item->getById($id);
				if ($result)
				{
					return $result;
				}
			}
		}
		return null;
	}

	/**
	 * @return Block[]
	 */
	public function getBlocks()
	{
		return $this->getItemsByType('block');
	}

	/**
	 * @param string $blocId
	 * @return Block|null
	 */
	public function getBlockById($blocId)
	{
		foreach ($this->getBlocks() as $block)
		{
			if ($block->getId() == $blocId)
			{
				return $block;
			}
		}
		return null;
	}

	/**
	 * @return Container[]
	 */
	public function getContainers()
	{
		return $this->getItemsByType('container');
	}

	/**
	 * @param array $array
	 * @param string|null $idPrefix
	 * @return Item[]
	 */
	public function fromArray($array, $idPrefix = null)
	{
		$result = [];
		foreach ($array as $key => $data)
		{
			if (isset($data['idPrefix']))
			{
				$idPrefix = $data['idPrefix'];
			}
			elseif ($idPrefix)
			{
				$data['idPrefix'] = $idPrefix;
			}

			$type = $data['type'];
			$id = $data['id'];
			$item = $this->getNewItem($type, $id);
			if ($item === null)
			{
				continue;
			}
			$item->initialize($data);
			if (isset($data['items']) && count($data['items']))
			{
				$item->setItems($this->fromArray($data['items']), $idPrefix);
			}
			else
			{
				$item->setItems([]);
			}
			$result[$key] = $item;
		}
		return $result;
	}

	public function toArray()
	{
		$result = [];
		if (!is_array($this->items))
		{
			return $result;
		}
		foreach ($this->items as $key => $item)
		{
			if ($item instanceof Item)
			{
				$result[$key] = $item->toArray();
			}
		}
		return $result;
	}

	/**
	 * @param string $type
	 * @param string $id
	 * @throws \InvalidArgumentException
	 * @return Item
	 */
	public function getNewItem($type, $id)
	{
		switch ($type)
		{
			case 'container':
				$item = new Container();
				break;
			case 'row':
				$item = new Row();
				break;
			case 'cell':
				$item = new Cell();
				break;
			case 'block':
				$item = new Block();
				break;
			case 'block-chooser':
				return null;
			default:
				throw new \InvalidArgumentException('Argument 1 must be a valid type', 999999);
		}

		$item->setId($id);
		return $item;
	}
}