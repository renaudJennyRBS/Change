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
 * @name \Change\Presentation\Layout\Row
 */
class Row extends Item
{
	/**
	 * @var integer
	 */
	protected $grid;

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'row';
	}

	/**
	 * @param integer $grid
	 */
	public function setGrid($grid)
	{
		$this->grid = $grid;
	}

	/**
	 * @return integer
	 */
	public function getGrid()
	{
		return $this->grid;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function initialize(array $data)
	{
		parent::initialize($data);
		$this->grid = $data['grid'];
	}

	public function toArray()
	{
		$result = parent::toArray();
		$result['grid'] = $this->grid;
		return $result;
	}
}