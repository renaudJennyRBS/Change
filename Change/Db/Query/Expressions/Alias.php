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
 * @name \Change\Db\Query\Expressions\Alias
 */
class Alias extends BinaryOperation
{
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $lhs
	 * @param \Change\Db\Query\Expressions\AbstractExpression $rhs
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $lhs = null, \Change\Db\Query\Expressions\AbstractExpression $rhs = null)
	{
		parent::__construct($lhs, $rhs, 'AS');
	}
}