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
 * @name \Change\Db\Query\Expressions\Raw
 */
class String extends \Change\Db\Query\Expressions\Value
{
	
	/**
	 * @param string $string
	 */
	public function __construct($string = null)
	{
		parent::__construct($string, \Change\Db\ScalarType::STRING);
	}
}