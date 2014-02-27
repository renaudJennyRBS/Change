<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Blocks;

/**
 * @name \Change\Presentation\Blocks\ParameterMeta
 */
class ParameterMeta
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var mixed|null
	 */
	protected $defaultValue;

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 */
	function __construct($name, $defaultValue = null)
	{
		$this->name = $name;
		$this->defaultValue = $defaultValue;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed|null $defaultValue
	 * @return $this
	 */
	public function setDefaultValue($defaultValue)
	{
		$this->defaultValue = $defaultValue;
		return $this;
	}

	/**
	 * @return mixed|null
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}
}