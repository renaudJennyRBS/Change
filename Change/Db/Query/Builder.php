<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query;

use Change\Db\DbProvider;

/**
 * @api
 * @name \Change\Db\Query\Builder
 */
class Builder extends AbstractBuilder
{

	/**
	 * If you are looking to get a builder instance, please get it from
	 * the application services which will properly inject the correct DB provider for you.
	 * @param DbProvider $dbProvider
	 * @param string $cacheKey
	 * @param SelectQuery $query
	 */
	public function __construct(DbProvider $dbProvider, $cacheKey = null, SelectQuery $query = null)
	{
		$this->setDbProvider($dbProvider);
		if ($cacheKey !== null)
		{
			$this->cacheKey = $cacheKey;
			$this->query = $query;
		}
	}

	/**
	 * Start building the select query. You can pass any number of AbstractExpression to this
	 * method corresponding to the (derived-)columns you wish to select.
	 * @api
	 * @param Expressions\AbstractExpression|string $column_1 [optional]
	 * @param Expressions\AbstractExpression|string $_  [optional]
	 * @return $this;
	 */
	public function select($column_1 = null, $_ = null)
	{
		if ($this->query)
		{
			$this->reset();
		}
		$this->query = new SelectQuery($this->dbProvider, $this->cacheKey);
		$selectClause = new Clauses\SelectClause();
		$this->query()->setSelectClause($selectClause);
		foreach (func_get_args() as $column)
		{
			if ($column !== null)
			{
				$this->addColumn($column);
			}
		}
		return $this;
	}
	
	/**
	 * Add a column expression to the existing select clause
	 * @api
	 * @param Expressions\AbstractExpression|string $expression
	 * @throws \LogicException
	 * @return $this
	 */
	public function addColumn($expression)
	{
		if (is_string($expression))
		{
			$expression = $this->getFragmentBuilder()->column($expression);
		}
		$this->query()->getSelectClause()->addSelect($expression);
		return $this;
	}
		
	/**
	 * Build a "SELECT DISTINCT ..." query.
	 * 
	 * @api
	 * @return $this
	 */
	public function distinct()
	{
		$this->query()->getSelectClause()->setQuantifier(Clauses\SelectClause::QUANTIFIER_DISTINCT);
		return $this;
	}
	
	/**
	 * Build the "FROM" clause. You can pass a string (table name), a \Change\Db\Query\Expressions\Table object or an \Change\Db\Query\Expressions\Alias to a table object.
	 * @api
	 * @see Builder::table()
	 * @see Builder::alias()
	 * @throws \InvalidArgumentException
	 * @param string|Expressions\Table|Expressions\Alias $table
	 * @return $this
	 */
	public function from($table)
	{
		if (is_string($table))
		{
			$tableExpression = $this->getFragmentBuilder()->table($table);
		}
		elseif ($table instanceof Expressions\Table || $table instanceof Expressions\Alias)
		{
			$tableExpression = $table;
		}
		else
		{
			throw new \InvalidArgumentException('first argument must be a string, a Expressions\Table, or a Expressions\Alias', 42003);
		}
		$fromClause = new Clauses\FromClause();
		$fromClause->setTableExpression($tableExpression);
		$this->query()->setFromClause($fromClause);
		return $this;
	}

	/**
	 * @api
	 * @param Predicates\InterfacePredicate $predicate
	 * @return $this
	 */
	public function where(Predicates\InterfacePredicate $predicate)
	{
		$whereClause = new Clauses\WhereClause($predicate);
		$this->query()->setWhereClause($whereClause);
		return $this;
	}
	
	/**
	 * @api
	 * @param  Expressions\AbstractExpression $tableExpression
	 * @param  Expressions\AbstractExpression $joinCondition
	 * @return $this
	 */
	public function innerJoin(Expressions\AbstractExpression $tableExpression, $joinCondition = null)
	{
		$join = new Expressions\Join($tableExpression, Expressions\Join::INNER_JOIN, $this->processJoinCondition($joinCondition));
		$this->query()->getFromClause()->addJoin($join);
		return $this;
	}
	
	/**
	 * @api
	 * @param Expressions\AbstractExpression $tableExpression
	 * @param Expressions\AbstractExpression $joinCondition
	 * @return $this
	 */
	public function leftJoin(Expressions\AbstractExpression $tableExpression, $joinCondition = null)
	{
		$join = new Expressions\Join($tableExpression, Expressions\Join::LEFT_OUTER_JOIN, $this->processJoinCondition($joinCondition));
		$this->query()->getFromClause()->addJoin($join);
		return $this;
	}
	
	/**
	 * @api
	 * @param Expressions\AbstractExpression $tableExpression
	 * @param Expressions\AbstractExpression $joinCondition
	 * @return $this
	 */
	public function rightJoin(Expressions\AbstractExpression $tableExpression, $joinCondition = null)
	{
		$join = new Expressions\Join($tableExpression, Expressions\Join::RIGHT_OUTER_JOIN, $this->processJoinCondition($joinCondition));
		$this->query()->getFromClause()->addJoin($join);
		return $this;
	}
	
	/**
	 * @api
	 * @param Expressions\AbstractExpression $tableExpression
	 * @param Expressions\AbstractExpression $joinCondition
	 * @return $this
	 */
	public function fullJoin(Expressions\AbstractExpression $tableExpression, $joinCondition = null)
	{
		$join = new Expressions\Join($tableExpression, Expressions\Join::FULL_OUTER_JOIN, $this->processJoinCondition($joinCondition));
		$this->query()->getFromClause()->addJoin($join);
		return $this;
	}
	
	/**
	 * @api
	 * @param Expressions\AbstractExpression $tableExpression
	 * @return $this
	 */
	public function crossJoin(Expressions\AbstractExpression $tableExpression)
	{
		$join =  new Expressions\Join($tableExpression, Expressions\Join::CROSS_JOIN);
		$this->query()->getFromClause()->addJoin($join);
		return $this;
	}
	
	/**
	 * @param Expressions\AbstractExpression $joinCondition
	 * @return Expressions\UnaryOperation
	 */
	protected function processJoinCondition($joinCondition = null)
	{
		$joinExpr = null;
		if ($joinCondition instanceof Predicates\InterfacePredicate)
		{
			$joinExpr = new Expressions\UnaryOperation($joinCondition, 'ON');
		}
		elseif ($joinCondition instanceof Expressions\Column || $joinCondition instanceof Expressions\ExpressionList)
		{
			$p = new Expressions\Parentheses($joinCondition);
			$joinExpr = new Expressions\UnaryOperation($p, 'USING');
		}
		return $joinExpr;
	}
	
	/**
	 * @api
	 * @param Expressions\AbstractExpression $expression
	 * @return $this
	 */
	public function orderAsc(Expressions\AbstractExpression $expression)
	{
		$this->addOrder($expression, Expressions\OrderingSpecification::ASC);
		return $this;
	}
	
	/**
	 * @api
	 * @param Expressions\AbstractExpression $expression
	 * @return $this
	 */
	public function orderDesc(Expressions\AbstractExpression $expression)
	{
		$this->addOrder($expression, Expressions\OrderingSpecification::DESC);
		return $this;
	}

	/**
	 * @api
	 * @param Expressions\AbstractExpression $expression
	 * @param string $direction
	 * @return $this
	 */
	protected function addOrder(Expressions\AbstractExpression $expression, $direction)
	{
		$orderByClause = $this->query()->getOrderByClause();
		if ($orderByClause === null)
		{
			$orderByClause = new Clauses\OrderByClause();
			$this->query()->setOrderByClause($orderByClause);
		}
		$orderByClause->addExpression(new Expressions\OrderingSpecification($expression, $direction));
		return $this;
	}
	
	/**
	 * @api
	 * @param Expressions\AbstractExpression $expression
	 * @return $this
	 */
	public function group(Expressions\AbstractExpression $expression)
	{
		$groupByClause = $this->query()->getGroupByClause();
		if ($groupByClause === null)
		{
			$groupByClause = new Clauses\GroupByClause();
			$this->query()->setGroupByClause($groupByClause);
		}
		$groupByClause->addExpression($expression);
		return $this;
	}
	
	/**
	 * Add a predicate to the existing where clause in "OR" mode
	 * 
	 * @api
	 * @param Predicates\InterfacePredicate $predicate
	 * @return $this
	 */
	public function orWhere(Predicates\InterfacePredicate $predicate)
	{
		$existingWhereClause = $this->query()->getWhereClause();
		if ($existingWhereClause === null)
		{
			$this->where($predicate);
		}
		else
		{
			$existingWherePredicate = $existingWhereClause->getPredicate();
			$existingWhereClause->setPredicate(new Predicates\Disjunction($existingWherePredicate, $predicate));
		}
		return $this;
	}
	
	/**
	 * Add a predicate to the existing where clause in "AND" mode
	 * 
	 * @api
	 * @param Predicates\InterfacePredicate $predicate
	 * @return $this
	 */
	public function andWhere(Predicates\InterfacePredicate $predicate)
	{
		$existingWhereClause = $this->query()->getWhereClause();
		if ($existingWhereClause === null)
		{
			$this->where($predicate);
		}
		else
		{
			$existingWherePredicate = $existingWhereClause->getPredicate();
			$existingWhereClause->setPredicate(new Predicates\Conjunction($existingWherePredicate, $predicate));
		}
		return $this;
	}

	/**
	 * @api
	 * @throws \LogicException
	 * @return SelectQuery
	 */
	public function query()
	{
		if ($this->query === null)
		{
			throw new \LogicException('Call select() before', 42005);
		}

		if ($this->cacheKey)
		{
			$this->dbProvider->addBuilderQuery($this->query);
		}
		return $this->query;
	}
}