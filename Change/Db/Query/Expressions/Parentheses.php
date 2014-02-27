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
 * @name \Change\Db\Query\Expressions\Parentheses
 */
class Parentheses extends \Change\Db\Query\Expressions\AbstractExpression
{
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $expression;
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->setExpression($expression);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getExpression()
	{
		return $this->expression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function setExpression(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->expression = $expression;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return '(' . $this->getExpression()->toSQL92String() . ')';
	}
}