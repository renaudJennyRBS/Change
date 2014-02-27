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
 * @name \Change\Presentation\Layout\Block
 */
class Block extends Item
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string|null
	 */
	protected $visibility;

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'block';
	}

	/**
	 * @return string|null
	 */
	public function getVisibility()
	{
		return $this->visibility;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function initialize(array $data)
	{
		parent::initialize($data);
		$this->name = $data['name'];
		$this->visibility = isset($data['visibility']) ? $data['visibility']: null;
	}
}