<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Presentation;

/**
 * @name \Rbs\Generic\Presentation\Paginator
 */
class Paginator
{
	/**
	 * @var integer
	 */
	protected $totalCount;

	/**
	 * @var integer
	 */
	protected $pageNumber;

	/**
	 * @var integer
	 */
	protected $itemsPerPage;

	/**
	 * @var mixed[]
	 */
	protected $items;

	/**
	 * @param mixed[] $items
	 * @param integer $pageNumber
	 * @param integer|null $itemsPerPage
	 * @param integer|null $totalCount
	 */
	public function __construct($items, $pageNumber = 0, $itemsPerPage = null, $totalCount = null)
	{
		$this->setItems($items);
		$this->setPageNumber($pageNumber);
		$this->setItemsPerPage($itemsPerPage);
		$this->setTotalCount(($totalCount !== null) ? $totalCount : count($items));
	}

	/**
	 * @param \mixed[] $items
	 * @return $this
	 */
	public function setItems($items)
	{
		$this->items = $items;
		return $this;
	}

	/**
	 * @return \mixed[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param integer $itemsPerPage
	 * @return $this
	 */
	public function setItemsPerPage($itemsPerPage)
	{
		$this->itemsPerPage = $itemsPerPage;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getItemsPerPage()
	{
		return $this->itemsPerPage;
	}

	/**
	 * @param integer $pageNumber
	 * @return $this
	 */
	public function setPageNumber($pageNumber)
	{
		$this->pageNumber = $pageNumber;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getPageNumber()
	{
		return $this->pageNumber;
	}

	/**
	 * @param integer $totalCount
	 * @return $this
	 */
	public function setTotalCount($totalCount)
	{
		$this->totalCount = $totalCount;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getTotalCount()
	{
		return $this->totalCount;
	}

	/**
	 * @return integer
	 */
	public function getPageCount()
	{
		return ceil($this->totalCount / $this->itemsPerPage);
	}
} 