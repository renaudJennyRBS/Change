<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Price\Collection;

/**
 * @name \Rbs\Price\Collection\IsoCurrency
 */
class IsoCurrency implements \Change\Collection\ItemInterface
{
	/**
	 * @var String
	 */
	protected $label;

	/**
	 * @var String
	 */
	protected $code;

	public function __construct($label, $code)
	{
		$this->label = $label;
		$this->code = $code;
	}
	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->label;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->code . ' -  ' . $this->label;
	}
}