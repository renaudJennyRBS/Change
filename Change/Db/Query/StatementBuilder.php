<?php
namespace Change\Db\Query;

use Change\Db\DbProvider;

/**
 * @api
 * @name \Change\Db\Query\StatementBuilder
 */
class StatementBuilder extends AbstractBuilder
{
	/**
	 * @param DbProvider $dbProvider
	 * @param string $cacheKey
	 * @param $query
	 */
	public function __construct(DbProvider $dbProvider, $cacheKey = null, AbstractQuery $query = null)
	{
		$this->dbProvider = $dbProvider;
		if ($cacheKey !== null)
		{
			$this->cacheKey = $cacheKey;
			$this->query = $query;
		}
	}

	/**
	 * @api
	 * @param Expressions\Table|string $table
	 * @param Expressions\Column|string $column1 [optional]
	 * @param Expressions\Column|string $_ [optional]
	 * @return $this
	 */
	public function insert($table = null, $column1 = null, $_ = null)
	{
		if ($this->query)
		{
			$this->reset();
		}
		$insertQuery = new InsertQuery($this->dbProvider, $this->cacheKey);
		$this->query = $insertQuery;

		$columns = func_get_args();
		array_shift($columns);

		if ($table)
		{
			if (is_string($table))
			{
				$table = $this->getFragmentBuilder()->table($table);
			}

			if ($table instanceof Expressions\Table)
			{
				$insertClause = new Clauses\InsertClause($table);
				$insertQuery->setInsertClause($insertClause);
			}
		}

		if (is_array($columns))
		{
			foreach ($columns as $column)
			{
				if ($column !== null)
				{
					$this->addColumn($column);
				}
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @throws \LogicException
	 * @return \Change\Db\Query\InsertQuery
	 */
	public function insertQuery()
	{
		if ($this->query instanceof InsertQuery)
		{
			if ($this->cacheKey)
			{
				$this->dbProvider->addStatementBuilderQuery($this->query);
			}
			return $this->query;
		}	
		throw new \LogicException('Call insert() before', 42017);
	}
	

	
	/**
	 * Add a columns to the existing insert clause
	 *
	 * @api
	 * @param Expressions\Column|string $column1 [optional]
	 * @param Expressions\Column|string $_ [optional]
	 * @throws \LogicException
	 * @return $this
	 */
	public function addColumns($column1 = null, $_ = null)
	{
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
	 * Add a column  to the existing insert clause
	 *
	 * @api
	 * @param Expressions\Column|string $column
	 * @throws \LogicException
	 * @return $this
	 */
	public function addColumn($column)
	{
		if (is_string($column))
		{
			$column = $this->getFragmentBuilder()->column($column);
		}

		$insertClause = $this->insertQuery()->getInsertClause();
		if ($insertClause === null)
		{
			$insertClause = new Clauses\InsertClause();
			$this->insertQuery()->setInsertClause($insertClause);
		}
		$insertClause->addColumn($column);
		return $this;
	}
	
	/**
	 * Add a values to the existing insert clause
	 *
	 * @api
	 * @param Expressions\AbstractExpression|string $value1 [optional]
	 * @param Expressions\AbstractExpression|string $_ [optional]
	 * @throws \LogicException
	 * @return $this
	 */
	public function addValues($value1, $_)
	{
		foreach (func_get_args() as $value)
		{
			if ($value !== null)
			{
				$this->addValue($value);
			}
		}
		return $this;
	}
	
	/**
	 * Add a column  to the existing insert clause
	 *
	 * @api
	 * @param Expressions\AbstractExpression|string $expression
	 * @throws \LogicException
	 * @return $this
	 */
	public function addValue($expression)
	{
		if (is_string($expression))
		{
			$expression = $this->getFragmentBuilder()->string($expression);
		}
		$valuesClause = $this->insertQuery()->getValuesClause();
		if ($valuesClause === null)
		{
			$valuesClause = new Clauses\ValuesClause();
			$this->insertQuery()->setValuesClause($valuesClause);
		}
		$valuesClause->addValue($expression);
		return $this;
	}

	/**
	 * @api
	 * @param Expressions\Table|string $table
	 * @return $this
	 */
	public function update($table = null)
	{
		if ($this->query)
		{
			$this->reset();
		}
		$this->query = new UpdateQuery($this->dbProvider, $this->cacheKey);

		if ($table)
		{
			if (is_string($table))
			{
				$table = $this->getFragmentBuilder()->table($table);
			}

			if ($table instanceof Expressions\Table)
			{
				$updateClause = new Clauses\UpdateClause($table);
				$this->updateQuery()->setUpdateClause($updateClause);
			}
		}
		return $this;
	}
	
	/**
	 * @api
	 * @throws \LogicException
	 * @return \Change\Db\Query\UpdateQuery
	 */
	public function updateQuery()
	{
		if ($this->query instanceof UpdateQuery)
		{
			if ($this->cacheKey)
			{
				$this->dbProvider->addStatementBuilderQuery($this->query);
			}
			return $this->query;
		}
		throw new \LogicException('Call update() before', 42018);
	}
	

	/**
	 * @api
	 * @param Expressions\Column|string $column
	 * @param Expressions\AbstractExpression|string $expression
	 * @throws \LogicException
	 * @return $this
	 */
	public function assign($column, $expression)
	{
		if (is_string($column))
		{
			$column = $this->getFragmentBuilder()->column($column);
		}
		if (is_string($expression))
		{
			$expression = $this->getFragmentBuilder()->string($expression);
		}
		$setClause = $this->updateQuery()->getSetClause();
		if ($setClause === null)
		{
			$setClause = new Clauses\SetClause();
			$this->updateQuery()->setSetClause($setClause);
		}
		$setClause->addSet($this->getFragmentBuilder()->assignment($column, $expression));
		return $this;
	}

	/**
	 * @api
	 * @param Expressions\Table|string $table
	 * @return $this
	 */
	public function delete($table = null)
	{
		if ($this->query)
		{
			$this->reset();
		}
		$this->query = new DeleteQuery($this->dbProvider, $this->cacheKey);

		if ($table)
		{
			if (is_string($table))
			{
				$table = $this->getFragmentBuilder()->table($table);
			}
			if ($table instanceof Expressions\Table)
			{
				$this->deleteQuery()->setDeleteClause(new Clauses\DeleteClause());
				$fromClause = new Clauses\FromClause($table);
				$this->deleteQuery()->setFromClause($fromClause);
			}
		}
		return $this;
	}
	
	/**
	 * @api
	 * @throws \LogicException
	 * @return \Change\Db\Query\DeleteQuery
	 */
	public function deleteQuery()
	{
		if ($this->query instanceof DeleteQuery)
		{
			if ($this->cacheKey)
			{
				$this->dbProvider->addStatementBuilderQuery($this->query);
			}
			return $this->query;
		}
	
		throw new \LogicException('Call delete() before', 42019);
	}
	


	/**
	 * @api
	 * @param Predicates\InterfacePredicate $predicate
	 * @throws \LogicException
	 * @return $this
	 */
	public function where(Predicates\InterfacePredicate $predicate)
	{
		$validQuery = $this->query;
		if ($validQuery instanceof UpdateQuery || $validQuery instanceof DeleteQuery)
		{
			/* @var $validQuery UpdateQuery|DeleteQuery */
			$whereClause = new Clauses\WhereClause($predicate);
			$validQuery->setWhereClause($whereClause);
			return $this;
		}
		throw new \LogicException('Call update() or delete() before', 42020);
	}
}