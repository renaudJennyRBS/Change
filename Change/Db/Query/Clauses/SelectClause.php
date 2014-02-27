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
 * @name \Change\Db\Query\Clauses\SelectClause
 * @api
 */
class SelectClause extends AbstractClause
{
	const QUANTIFIER_DISTINCT = 'DISTINCT';
	const QUANTIFIER_ALL = 'ALL';
	
	/**
	 * @var string
	 */
	protected $quantifier = self::QUANTIFIER_ALL; 
	
	/**	  
	 * @var \Change\Db\Query\Expressions\ExpressionList
	 */
	protected $selectList;
	
	/**
	 * @param \Change\Db\Query\Expressions\ExpressionList $list
	 */
	public function __construct(\Change\Db\Query\Expressions\ExpressionList $list = null)
	{
		$this->setName('SELECT');
		$this->setSelectList($list ? $list : new \Change\Db\Query\Expressions\ExpressionList());
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function getSelectList()
	{
		return $this->selectList;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\ExpressionList $expression
	 */
	public function setSelectList(\Change\Db\Query\Expressions\ExpressionList $expression)
	{
		$this->selectList = $expression;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Clauses\SelectClause
	 */
	public function addSelect(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->selectList->add($expression);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getQuantifier()
	{
		return $this->quantifier;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @param string $quantifier
	 */
	public function setQuantifier($quantifier)
	{
		switch ($quantifier) 
		{
			case self::QUANTIFIER_DISTINCT:
			case self::QUANTIFIER_ALL:
				$this->quantifier = $quantifier;
				return;
		}
		throw new \InvalidArgumentException('Argument 1 must be a valid const', 42027);
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$parts = array($this->getName());
		if ($this->getQuantifier() === self::QUANTIFIER_DISTINCT)
		{
			$parts[] = self::QUANTIFIER_DISTINCT;
		}
		$selectList = $this->getSelectList();
		if ($selectList->count() === 0)
		{
			$selectList->add(new \Change\Db\Query\Expressions\AllColumns());
		}
		
		$parts[] = $selectList->toSQL92String();
		return implode(' ', $parts);
	}
}