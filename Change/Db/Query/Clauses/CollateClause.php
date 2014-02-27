<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Clauses;

/**
 * @name \Change\Db\Query\Clauses\CollateClause
 */
class CollateClause extends AbstractClause
{
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $expression;
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $expression = null)
	{
		$this->setName('COLLATE');
		if ($expression)
		{
			$this->setExpression($expression);
		}
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression|null
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
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->getExpression() === null)
		{
			throw new \RuntimeException('Expression can not be null', 42023);
		}
		return 'COLLATE ' . $this->getExpression()->toSQL92String();
	}
}