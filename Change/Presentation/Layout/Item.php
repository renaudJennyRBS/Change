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
 * @name \Change\Presentation\Layout\Item
 */
abstract class Item
{
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var Item[]
	 */
	protected $items;

	/**
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param Item[] $items
	 */
	public function setItems($items)
	{
		$this->items = $items;
	}

	/**
	 * @return Item[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @return string
	 */
	abstract public function getType();

	/**
	 * @param array $data
	 * @return void
	 */
	public function initialize(array $data)
	{
		$this->id = $data['id'];

		if (isset($data['parameters']) && count($data['parameters']))
		{
			$this->setParameters($data['parameters']);
		}
		else
		{
			$this->setParameters(array());
		}
	}

	/**
	 * @param string $type
	 * @return Item[]
	 */
	public function getItemsByType($type)
	{
		$result = ($this->getType() === $type) ? array($this) : array();
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
	 * @return Block[]
	 */
	public function getBlocks()
	{
		return $this->getItemsByType('block');
	}

	/**
	 * @param string $id
	 * @return Item|null
	 */
	public function getById($id)
	{
		if ($id === $this->getId())
		{
			return $this;
		}
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

	public function toArray()
	{
		$result = ['type' => $this->getType(), 'id' => $this->id,
			'parameters' => is_array($this->parameters) ? $this->parameters : []];
		if (!is_array($this->items))
		{
			return $result;
		}
		foreach ($this->items as $key => $item)
		{
			if ($item instanceof Item)
			{
				$result['items'][$key] = $item->toArray();
			}
		}
		return $result;
	}
}