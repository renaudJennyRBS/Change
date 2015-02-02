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
	 * @var string|null
	 */
	protected $idPrefix;

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
		return $this->idPrefix ? $this->idPrefix . $this->id : $this->id;
	}

	/**
	 * @return string
	 */
	public function getIdPrefix()
	{
		return $this->idPrefix;
	}

	/**
	 * @param string|null $idPrefix
	 */
	public function initIdPrefix($idPrefix)
	{
		$this->idPrefix = $idPrefix;

		$items = $this->getItems();
		if ($items && $idPrefix)
		{
			foreach ($items as $item)
			{
				$item->initIdPrefix($idPrefix);
			}
		}
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
			$this->setParameters([]);
		}

		if (isset($data['idPrefix']))
		{
			$this->idPrefix = $data['idPrefix'];
		}
	}

	/**
	 * @param string $type
	 * @return Item[]
	 */
	public function getItemsByType($type)
	{
		$result = ($this->getType() === $type) ? [$this] : [];
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