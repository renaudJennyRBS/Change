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
 * @name \Change\Db\Query\Clauses\GroupByClause
 * @api
 */
class GroupByClause extends AbstractClause
{
	/**
	 * @var \Change\Db\Query\Expressions\ExpressionList
	 */
	protected $expressionList;
	
	/**
	 * @param \Change\Db\Query\Expressions\ExpressionList $expressionList
	 */
	public function __construct(\Change\Db\Query\Expressions\ExpressionList $expressionList = null)
	{
		$this->setName('GROUP BY');
		if ($expressionList)
		{
			$this->setExpressionList($expressionList);
		}
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function getExpressionList()
	{
		return $this->expressionList;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\ExpressionList $expressionList
	 */
	public function setExpressionList(\Change\Db\Query\Expressions\ExpressionList $expressionList)
	{
		$this->expressionList = $expressionList;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Clauses\GroupByClause
	 */
	public function addExpression(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$list = $this->getExpressionList();
		if ($list === null)
		{
			$list = new \Change\Db\Query\Expressions\ExpressionList();
			$this->setExpressionList($list);
		}
		$list->add($expression);
		return $this;
	}
	
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if ($this->getExpressionList() === null)
		{
			throw new \RuntimeException('ExpressionList can not be null', 42025);
		}
	}
	
	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		return 'GROUP BY ' . $this->getExpressionList()->toSQL92String();
	}
}