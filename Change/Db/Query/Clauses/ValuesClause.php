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
 * @name \Change\Db\Query\Clauses\ValuesClause
 * @api
 */
class ValuesClause extends AbstractClause
{	
	/**	  
	 * @var \Change\Db\Query\Expressions\ExpressionList
	 */
	protected $valueList;
	
	/**
	 * @param array $values
	 */
	public function __construct(\Change\Db\Query\Expressions\ExpressionList $values = null)
	{
		$this->setName('VALUES');
		$this->setValuesList($values ? $values : new \Change\Db\Query\Expressions\ExpressionList());
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function getValuesList()
	{
		return $this->valueList;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\ExpressionList
	 */
	public function setValuesList(\Change\Db\Query\Expressions\ExpressionList $values)
	{
		$this->valueList = $values;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Clauses\ValuesClause
	 */
	public function addValue(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->valueList->add($expression);
		return $this;
	}
	
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if (!$this->getValuesList()->count())
		{
			throw new \RuntimeException('ValuesList can not be empty', 42029);
		}
	}
		
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		return 'VALUES ('. $this->getValuesList()->toSQL92String() . ')';
	}
}