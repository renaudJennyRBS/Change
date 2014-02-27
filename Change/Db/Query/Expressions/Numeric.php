<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Numeric
 */
class Numeric extends \Change\Db\Query\Expressions\Value
{
	/**
	 * @param integer|float $value
	 */
	public function __construct($value = null)
	{
		parent::__construct($value, \Change\Db\ScalarType::DECIMAL);
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->value === null)
		{
			return 'NULL';
		}
		return strval(floatval($this->value));
	}
}