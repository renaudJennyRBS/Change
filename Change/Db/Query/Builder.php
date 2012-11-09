<?php
namespace Change\Db\Query;

use Zend\Db\Sql\Expression;

class Builder
{
	/**
	 * @var \Change\Db\Query\SelectQuery
	 */
	protected $query;
	
	/**
	 * 
	 * @var \Change\Db\Query
	 */
	protected $dbProvider;
	
	/**
	 * If you are looking to get a builder instance, please get it from
	 * the application services which will properly inject the correct DB provider for you
	 * 
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function __construct(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}
	
	/**
	 * Start building the select query. You can pass any number of AbstractExpression to this
	 * method corresponding to the (derived-)columns you wish to select.
	 * 
	 * @api
	 * @return \Change\Db\Query\Builder
	 */
	public function select()
	{
		if ($this->query)
		{
			$this->reset();
		}
		$this->query = new SelectQuery($this->dbProvider);
		$selectClause = new \Change\Db\Query\Clauses\SelectClause();
		$builder = $this;
		$normalizedArgs = $this->normalizeValue(func_get_args(), function($item) use ($builder){
			return $builder->column(strval($item));
		});
		if (count($normalizedArgs) > 0)
		{
			$selectList = new Expressions\ExpressionList($normalizedArgs);
			$selectClause->setSelectList($selectList);
		}
		$this->query->setSelectClause($selectClause);
		return $this;
	}
	
	/**
	 * Explicitely reset the builder (which will destroy the current query).
	 * 
	 * @api
	 */
	public function reset()
	{
		$this->query = null;
	}
	
	/**
	 * Build a "SELECT DISCTINCT ..." query.
	 * 
	 * @api
	 * @return \Change\Db\Query\Builder
	 */
	public function distinct()
	{
		$this->query()->getSelectClause()->setQuantifier(\Change\Db\Query\Clauses\SelectClause::QUANTIFIER_DISTINCT);
		return $this;
	}
	
	/**
	 * Build the "FROM" clause. You can pass a string (table name), a \Change\Db\Query\Expressions\Table object or an \Change\Db\Query\Expressions\Alias to a table object.
	 * @api
	 * @see Builder::table()
	 * @see Builder::alias()
	 * @param string | \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Alias
	 * @return \Change\Db\Query\Builder
	 */
	public function from($table)
	{
		if (is_string($table))
		{
			$tableExpression = $this->table($tableNameOrTableObject);
		}
		elseif ($table instanceof \Change\Db\Query\Expressions\Table || $table instanceof \Change\Db\Query\Expressions\Alias)
		{
			$tableExpression = $table;
		}
		else
		{
			throw new \InvalidArgumentException('first argument must be a string, a \Change\Db\Query\Expressions\Table, or a \Change\Db\Query\Expressions\Alias');
		}
		$args = $this->normalizeValue(func_get_args());
		if (count($args) === 0)
		{
			throw new \InvalidArgumentException(__METHOD__ . ' requires at least on argument');
		}
		$fromClause = new Clauses\FromClause();
		$fromClause->setTableExpression($tableExpression);
		$this->query->getSelectClause()->setFromClause($fromClause);
		return $this;
	}
	
	/**
	 * Add a column expression to the existing select clause
	 * 
	 * @api
	 * @param \Change\Db\Query\Expressions\AbstractExpression|string $expression
	 * @throws \LogicException
	 * @return \Change\Db\Query\Builder
	 */
	public function addColumn($expression)
	{
		if (is_string($expression))
		{
			$expression = $this->column($expression);
		}
		if (!($this->query() instanceof \Change\Db\Query\SelectQuery))
		{
			throw new \LogicException('Call select() before trying to call addSelectColumn');
		}
		$selectClause = $this->query()->getSelectClause();
		if ($selectClause === null)
		{
			$selectClause = new \Change\Db\Query\Clauses\SelectClause();
			$this->query()->setSelectClause($selectClause);
		}
		$selectClause->addSelect($expression);
		return $this;
	}
	
	/**
	 * @param \Change\Db\Query\Predicates\InterfacePredicate $predicate
	 * @return \Change\Db\Query\Builder
	 */
	public function where(\Change\Db\Query\Predicates\InterfacePredicate $predicate)
	{
		$whereClause = new \Change\Db\Query\Clauses\WhereClause($predicate);
		$this->query()->getSelectClause()->setWhereClause($whereClause);
		return $this;
	}
	
	/**
	 * @param  \Change\Db\Query\Expressions\AbstractExpression $tableExpression
	 * @param  \Change\Db\Query\Expressions\AbstractExpression $joinCondition
	 * @return \Change\Db\Query\Builder
	 */
	public function innerJoin(\Change\Db\Query\Expressions\AbstractExpression $tableExpression, $joinCondition = null)
	{
		$join = new \Change\Db\Query\Expressions\Join($tableExpression, \Change\Db\Query\Expressions\Join::INNER_JOIN, $this->processJoinCondition($joinCondition));	
		$this->query->getSelectClause()->getFromClause()->addJoin($join);
		return $this;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $tableExpression
	 * @param \Change\Db\Query\Expressions\AbstractExpression $joinCondition
	 * @return \Change\Db\Query\Expressions\Join
	 */
	public function leftJoin(\Change\Db\Query\Expressions\AbstractExpression $tableExpression, $joinCondition = null)
	{
		return new \Change\Db\Query\Expressions\Join($tableExpression, \Change\Db\Query\Expressions\Join::LEFT_OUTER_JOIN, $this->processJoinCondition($joinCondition));
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $tableExpression
	 * @param \Change\Db\Query\Expressions\AbstractExpression $joinCondition
	 * @return \Change\Db\Query\Expressions\Join
	 */
	public function rightJoin(\Change\Db\Query\Expressions\AbstractExpression $tableExpression, $joinCondition = null)
	{
		return new \Change\Db\Query\Expressions\Join($tableExpression, \Change\Db\Query\Expressions\Join::RIGHT_OUTER_JOIN, $this->processJoinCondition($joinCondition));
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $tableExpression
	 * @param \Change\Db\Query\Expressions\AbstractExpression $joinCondition
	 * @return \Change\Db\Query\Expressions\Join
	 */
	public function fullJoin(\Change\Db\Query\Expressions\AbstractExpression $tableExpression, $joinCondition = null)
	{
		return new \Change\Db\Query\Expressions\Join($tableExpression, \Change\Db\Query\Expressions\Join::FULL_OUTER_JOIN, $this->processJoinCondition($joinCondition));
	}
	
	/**
	 * 
	 * @param \Change\Db\Query\Expressions\AbstractExpression $tableExpression
	 * @return \Change\Db\Query\Expressions\Join
	 */
	public function crossJoin(\Change\Db\Query\Expressions\AbstractExpression $tableExpression)
	{
		return new \Change\Db\Query\Expressions\Join($tableExpression, \Change\Db\Query\Expressions\Join::CROSS_JOIN);
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $joinCondition
	 * @return \Change\Db\Query\Expressions\UnaryOperation
	 */
	protected function processJoinCondition($joinCondition = null)
	{
		$joinExpr = null;
		if ($joinCondition instanceof \Change\Db\Query\Predicates\InterfacePredicate)
		{
			$joinExpr =  new \Change\Db\Query\Expressions\UnaryOperation($joinCondition, 'ON');
		}
		elseif ($joinCondition instanceof \Change\Db\Query\Expressions\Column || $joinCondition instanceof \Change\Db\Query\Expressions\ExpressionList)
		{
			$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($joinCondition, 'USING');
		}
		return $joinExpr;
	}
	
	
	/**
	 * 
	 * @param string $name
	 * @param array $args
	 */
	public function func($name)
	{
		$funcArgs = func_get_args();
		array_shift($funcArgs);
		return new \Change\Db\Query\Expressions\Func($name, $this->normalizeValue($funcArgs));
	}
	
	/**
	 * 
	 * @api
	 * @return \Change\Db\Query\Expressions\Func
	 */
	public function sum()
	{
		return new \Change\Db\Query\Expressions\Func('SUM', $this->normalizeValue(func_get_args()));
	}
	
	/**
	 * Build a reference to a given table 
	 * 
	 * @param string $tableName
	 * @param string $dbName
	 * @return \Change\Db\Query\Expressions\Table
	 */
	public function table($tableName, $dbName = null)
	{
		return new \Change\Db\Query\Expressions\Table($tableName, $dbName);
	}
	
	/**
	 * @api
	 * @param string $name
	 * @param \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Identifier | string $tableOrIdentifier
	 * @return \Change\Db\Query\Expressions\Column
	 */
	public function column($name, $tableOrIdentifier = null)
	{
		if (is_string($tableOrIdentifier))
		{
			$tableOrIdentifier = new \Change\Db\Query\Expressions\Identifier(array($tableOrIdentifier));
		}
		return new \Change\Db\Query\Expressions\Column($name, $tableOrIdentifier);
	}
	
	/**
	 * @param \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Expressions\Alias
	 */
	public function alias(\Change\Db\Query\Expressions\AbstractExpression $lhs, $rhs)
	{
		if (is_string($rhs))
		{
			$rhs = $this->identifier($rhs);
		}
		if (!($rhs instanceof \Change\Db\Query\Expressions\AbstractExpression))
		{
			throw new \InvalidArgumentException('Could not convert argument 2 to an Expression');
		}
		return new Expressions\Alias($lhs, $rhs);
	}
	
	
	/**
	 * Build an identifier string (eg: `test` on MySQL) which can be passed
	 * for instance as the second argument of the alias method
	 * 
	 * @api
	 * @param string $tableName
	 * @param string $dbName
	 */
	public function identifier()
	{
		return new \Change\Db\Query\Expressions\Identifier(func_get_args());
	}
	
	/**
	 * @api
	 * @param string $parameter
	 * @return \Change\Db\Query\Expressions\Parameter
	 */
	public function parameter($parameter)
	{
		$this->query->addParameter($parameter);
		return new \Change\Db\Query\Expressions\Parameter($parameter);
	}
	
	/**
	 *
	 * @param numeric $number
	 * @return \Change\Db\Query\Expressions\Numeric
	 */
	public function number($number)
	{
		return new \Change\Db\Query\Expressions\Numeric($number);
	}
	
	/**
	 * 
	 * @param unknown_type $string
	 * @return \Change\Db\Query\Expressions\String
	 */
	public function string($string)
	{
		return new \Change\Db\Query\Expressions\String($string);
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function expressionList()
	{
		return new \Change\Db\Query\Expressions\ExpressionList(func_get_args());
	}
	
	/**
	 * @param \Change\Db\Query\SelectQuery $selectQuery
	 * @return \Change\Db\Query\Expressions\Subquery
	 */
	public function subQuery(\Change\Db\Query\SelectQuery $selectQuery)
	{
		return new \Change\Db\Query\Expressions\SubQuery($selectQuery);
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Builder
	 */
	public function orderAsc(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->addOrder($expression, \Change\Db\Query\Expressions\OrderingSpecification::ASC);
		return $this;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Builder
	 */
	public function orderDesc(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->addOrder($expression, \Change\Db\Query\Expressions\OrderingSpecification::DESC);
		return $this;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\ExpressionList $list
	 * @param string $direction
	 */
	protected function addOrder(\Change\Db\Query\Expressions\AbstractExpression $expression, $direction)
	{
		$orderByClause = $this->query->getSelectClause()->getOrderByClause();
		if ($orderByClause === null)
		{
			$orderByClause = new \Change\Db\Query\Clauses\OrderByClause();
			$this->query->getSelectClause()->setOrderByClause($orderByClause);
		}
		$orderByClause->addExpression(new \Change\Db\Query\Expressions\OrderingSpecification($expression, $direction));
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Builder
	 */
	public function group(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$groupByClause = $this->query->getSelectClause()->getGroupByClause();
		if ($groupByClause === null)
		{
			$groupByClause = new \Change\Db\Query\Clauses\GroupByClause();
			$this->query->getSelectClause()->setGroupByClause($groupByClause);
		}
		$groupByClause->addExpression($expression);
		return $this;
	}
	
	/**
	 * Add a predicate to the existing where clause in "OR" mode
	 * 
	 * @api
	 * @param  \Change\Db\Query\Predicates\InterfacePredicate $predicate
	 * @return \Change\Db\Query\Builder
	 */
	public function orWhere(\Change\Db\Query\Predicates\InterfacePredicate $predicate)
	{
		$existingWhereClause = $this->query()->getSelectClause()->getWhereClause();
		if ($existingWhereClause === null)
		{
			$this->where($predicate);
		}
		else
		{
			$existingWherePredicate = $existingWhereClause->getPredicate();
			$existingWhereClause->setPredicate(new \Change\Db\Query\Predicates\Disjunction($existingWherePredicate, $predicate));
		}
		return $this;
	}
	
	/**
	 * Add a predicate to the existing where clause in "AND" mode
	 * 
	 * @api
	 * @param \Change\Db\Query\Predicates\InterfacePredicate $predicate
	 * @return \Change\Db\Query\Builder
	 */
	public function andWhere(\Change\Db\Query\Predicates\InterfacePredicate $predicate)
	{
		$existingWhereClause = $this->query()->getSelectClause()->getWhereClause();
		if ($existingWhereClause === null)
		{
			$this->where($predicate);
		}
		else
		{
			$existingWherePredicate = $existingWhereClause->getPredicate();
			$existingWhereClause->setPredicate(new \Change\Db\Query\Predicates\Conjunction($existingWherePredicate, $predicate));
		}
		return $this;
	}
	
	/**
	 * 
	 * @return \Change\Db\Query\Predicates\Conjunction
	 */
	public function logicAnd()
	{
		$result = new \Change\Db\Query\Predicates\Conjunction();
		$result->setArguments($this->normalizeValue(func_get_args()));
		return $result;
	}
	
	/**
	 *
	 * @return \Change\Db\Query\Predicates\Disjunction
	 */
	public function logicOr()
	{
		$result = new \Change\Db\Query\Predicates\Disjunction();
		$result->setArguments($this->normalizeValue(func_get_args()));
		return $result;
	}
	
	/**
	 * @param string | \Change\Db\Query\AbstractExpression $lhs
	 * @param string | \Change\Db\Query\AbstractExpression $rhs
	 * @return \Change\Db\Query\Predicates\Eq
	 */
	public function eq($lhs, $rhs)
	{
		$lhs = $this->normalizeValue($lhs);
		$rhs = $this->normalizeValue($rhs);
		return new \Change\Db\Query\Predicates\Eq($lhs, $rhs);
	}
	
	/**
	 * @return \Change\Db\Query\SelectQuery
	 */
	public function query()
	{
		return $this->query;
	}
	
	/**
	 * For internal use only
	 * 
	 * @param  \Change\Db\Query\AbstractExpression $object
	 * @return \Change\Db\Query\Expressions\Raw|\Change\Db\Query\Expressions\AbstractExpression
	 */
	public function normalizeValue($object, $converter = null)
	{
		if ($converter == null)
		{
			$converter = function($item){
				return  new \Change\Db\Query\Expressions\Raw(strval($item));
			};
		}
		if (is_array($object))
		{
			$builder = $this;
			return array_map(function($item) use ($builder, $converter){
					return $builder->normalizeValue($item, $converter);
				}, $object);
		}
		if (!($object instanceof \Change\Db\Query\Expressions\AbstractExpression))
		{
			return call_user_func($converter, $object);
		}
		return $object;
	}
}