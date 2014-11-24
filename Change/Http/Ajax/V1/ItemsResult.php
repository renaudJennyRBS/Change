<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Ajax\V1;

/**
 * @name \Change\Http\Ajax\V1\ItemsResult
 */
class ItemsResult extends \Change\Http\Result
{
	/**
	 * @var string
	 */
	protected $name = null;

	/**
	 * @var array
	 */
	protected $items = [];

	/**
	 * @var array
	 */
	protected $pagination = ['offset' => 0];

	/**
	 * @param string $name
	 * @param array $items
	 */
	function __construct($name = null, array $items = null)
	{
		parent::__construct(\Zend\Http\Response::STATUS_CODE_200);
		$this->setName($name);
		if (is_array($items))
		{
			$this->setItems($items);
		}
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param array $dataSets
	 * @return $this
	 */
	public function setItems(array $dataSets = [])
	{
		$this->items = $dataSets;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}


	/**
	 * @param array $pagination
	 * @return $this
	 */
	public function setPagination(array $pagination)
	{
		$this->pagination = $pagination;
		$this->setPaginationOffset(isset($pagination['offset']) ? $pagination['offset'] : 0);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getPagination()
	{
		return $this->pagination;
	}

	/**
	 * @param integer $offset
	 * @return $this
	 */
	public function setPaginationOffset($offset = null)
	{
		$this->pagination['offset'] = is_numeric($offset) ? intval($offset) :0;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getPaginationOffset()
	{
		return $this->pagination['offset'];
	}

	/**
	 * @param integer $count
	 * @return $this
	 */
	public function setPaginationCount($count = null)
	{
		if (is_numeric($count)) {
			$this->pagination['count'] = intval($count);
		}
		else
		{
			unset($this->pagination['count']);
		}
		return $this;
	}

	/**
	 * @return integer|null
	 */
	public function getPaginationCount()
	{
		return isset($this->pagination['count']) ? $this->pagination['count'] : null;
	}

	/**
	 * @param integer $limit
	 * @return $this
	 */
	public function setPaginationLimit($limit = null)
	{
		if (is_numeric($limit))
		{
			$this->pagination['limit'] = intval($limit);
		}
		else
		{
			unset($this->pagination['limit']);
		}
		return $this;
	}

	/**
	 * @return integer|null
	 */
	public function getPaginationLimit()
	{
		return isset($this->pagination['limit']) ? $this->pagination['limit'] : null;
	}


	/**
	 * @return array
	 */
	public function toArray()
	{
		$items = [];
		foreach ($this->items as $item)
		{
			if (is_object($item))
			{
				$callable = [$item, 'toArray'];
				if (is_callable($callable))
				{
					$item = call_user_func($callable);
				}
			}
			if (is_array($item))
			{
				$items[] = $item;
			}
		}
		return ['name'=> $this->name, 'pagination' => $this->pagination, 'items' => $items];
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return json_encode($this->toArray());
	}
}