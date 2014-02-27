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
 * @name \Change\Db\Query\Expressions\AbstractOperation
 */
abstract class AbstractOperation extends AbstractExpression
{	
	/**
	 * @var string
	 */
	protected $operator;
	
	/**
	 * @return string
	 */
	public function getOperator()
	{
		return $this->operator;
	}
	
	/**
	 * @param string $operator
	 */
	public function setOperator($operator)
	{
		$this->operator = $operator;
	}
}