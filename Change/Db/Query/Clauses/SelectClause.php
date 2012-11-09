<?php

namespace Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\AbstractClause;

class SelectClause extends AbstractClause
{
	const QUANTIFIER_DISTINCT = 'DISTINCT';
	const QUANTIFIER_ALL = 'ALL';
	
	/**
	 * @var string
	 */
	protected $quantifier;
	
	/**	  
	 * @var \Change\Db\Query\Expressions\ExpressionList
	 */
	protected $selectList;
	
	/**
	 *
	 * @var \Change\Db\Query\Clauses\FromClause
	 */
	protected $fromClause;
	
	/**
	 *
	 * @var \Change\Db\Query\Clauses\WhereClause
	 */
	protected $whereClause;
	
	/**
	 *
	 * @var \Change\Db\Query\Clauses\HavingClause
	 */
	protected $havingClause;
	
	/**
	 *
	 * @var \Change\Db\Query\Clauses\OrderByClause
	 */
	protected $orderByClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\GroupByClause
	 */
	protected $groupByClause;
	
	public function __construct(
		\Change\Db\Query\Expressions\ExpressionList $list = null,
		\Change\Db\Query\Clauses\FromClause $from = null,
		\Change\Db\Query\Clauses\WhereClause $where = null,
		\Change\Db\Query\Clauses\HavingClause $having = null,
		\Change\Db\Query\Clauses\OrderByClause $order = null,
		\Change\Db\Query\Clauses\GroupByClause $group = null)
	{
		if ($list) $this->setSelectList($list);
		if ($from) $this->setFromClause($from);
		if ($where) $this->setWhereClause($where);
		if ($having) $this->setHavingClause($having);
		if ($order) $this->setOrderByClause($order);
		if ($group) $this->setGroupByClause($group);
	}
	
	/**
	 *
	 * @return \Change\Db\Query\Clauses\FromClause
	 */
	public function getFromClause()
	{
		return $this->fromClause;
	}
	
	/**
	 *
	 * @param \Change\Db\Query\Clauses\FromClause $fromClause        	
	 */
	public function setFromClause(\Change\Db\Query\Clauses\FromClause $fromClause)
	{
		$this->fromClause = $fromClause;
	}
	
	/**
	 *
	 * @return \Change\Db\Query\Clauses\WhereClause
	 */
	public function getWhereClause()
	{
		return $this->whereClause;
	}
	
	/**
	 *
	 * @param \Change\Db\Query\Clauses\WhereClause $whereClause        	
	 */
	public function setWhereClause(\Change\Db\Query\Clauses\WhereClause $whereClause)
	{
		$this->whereClause = $whereClause;
	}
	
	/**
	 *
	 * @return \Change\Db\Query\Clauses\HavingClause
	 */
	public function getHavingClause()
	{
		return $this->havingClause;
	}
	
	/**
	 *
	 * @param \Change\Db\Query\Clauses\HavingClause $havingClause        	
	 */
	public function setHavingClause(\Change\Db\Query\Clauses\HavingClause $havingClause)
	{
		$this->havingClause = $havingClause;
	}
	
	/**
	 *
	 * @return \Change\Db\Query\Clauses\OrderByClause
	 */
	public function getOrderByClause()
	{
		return $this->orderByClause;
	}
	
	/**
	 *
	 * @param \Change\Db\Query\Clauses\OrderByClause $orderByClause        	
	 */
	public function setOrderByClause(\Change\Db\Query\Clauses\OrderByClause $orderByClause)
	{
		$this->orderByClause = $orderByClause;
	}
	
	/**
	 *
	 * @return \Change\Db\Query\Clauses\GroupByClause
	 */
	public function getGroupByClause()
	{
		return $this->groupByClause;
	}
	
	/**
	 *
	 * @param \Change\Db\Query\Clauses\GroupByClause $groupByClause        	
	 */
	public function setGroupByClause(\Change\Db\Query\Clauses\GroupByClause $groupByClause)
	{
		$this->groupByClause = $groupByClause;
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function getSelectList()
	{
		return $this->selectList;
	}
	
	/**
	 *
	 * @param \Change\Db\Query\Expressions\ExpressionList $expression       	
	 */
	public function setSelectList($expression)
	{
		$this->selectList = $expression;
	}
	
	/**
	 * 
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function addSelect($expression)
	{
		$this->selectList->add($expression);
	}
	
	/**
	 * @return string
	 */
	public function getQuantifier()
	{
		return $this->quantifier;
	}

	/**
	 * @param string $quantifier
	 */
	public function setQuantifier($quantifier)
	{
		$this->quantifier = $quantifier;
	}

	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$parts = array('SELECT');
		if ($this->getQuantifier() === self::QUANTIFIER_DISTINCT)
		{
			$parts[] = self::QUANTIFIER_DISTINCT;
		}
		$selectList = $this->getSelectList();
		if ($selectList === null)
		{
			$selectList = new \Change\Db\Query\Expressions\AllColumns();
		}
	
		$parts[] = $selectList->toSQL92String();
		
		$fromClause = $this->getFromClause();
		if ($fromClause)
		{
			$parts[] = $fromClause->toSQL92String();
		}
		$whereClause = $this->getWhereClause();
		if ($whereClause)
		{
			$parts[] = $whereClause->toSQL92String();
		}
		
		$groupByClause = $this->getGroupByClause();
		if ($groupByClause)
		{
			$parts[] = $groupByClause->toSQL92String();
		}
		

		$orderByClause = $this->getOrderByClause();
		if ($orderByClause)
		{
			$parts[] = $orderByClause->toSQL92String();
		}
		return implode(' ', $parts);
	}
}
