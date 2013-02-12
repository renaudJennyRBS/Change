<?php
namespace Change\Db\Query;

/**
 * @api
 * @name \Change\Db\Query\StatementBuilder
 */
class StatementBuilder
{
	/**
	 * @var \Change\Db\Query\AbstractQuery
	 */
	protected $query;
	
	/**
	 * 
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;
	
	/**
	 * @var \Change\Db\Query\SQLFragmentBuilder
	 */
	protected $fragmentBuilder;
	
	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function __construct(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
		$this->fragmentBuilder = new \Change\Db\Query\SQLFragmentBuilder($dbProvider->getSqlMapping());
	}
	
	/**
	 * @api
	 * Explicitly reset the builder (which will destroy the current query).
	 */
	public function reset()
	{
		$this->query = null;
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	public function getFragmentBuilder()
	{
		return $this->fragmentBuilder;
	}
	
	/**
	 * @api
	 * @return \Change\Db\SqlMapping
	 */
	public function getSqlMapping()
	{
		return $this->dbProvider->getSqlMapping();
	}

	/**
	 * @param \Change\Db\Query\SQLFragmentBuilder $fragmentBuilder
	 */
	public function setFragmentBuilder($fragmentBuilder)
	{
		$this->fragmentBuilder = $fragmentBuilder;
	}
	
	/**
	 * @api
	 * @throws \InvalidArgumentException
	 * @throws \LogicException
	 * @param string|\Change\Db\Query\Expressions\Parameter $parameter
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function addParameter($parameter)
	{
		if ($this->query === null)
		{
			throw new \LogicException('Query not initialized');
		}
		if (is_string($parameter))
		{
			$parameter = $this->fragmentBuilder->parameter($parameter);
		}
		if (!($parameter instanceof \Change\Db\Query\Expressions\Parameter))
		{
			throw new \InvalidArgumentException('argument must be a string or a \Change\Db\Query\Expressions\Parameter');
		}
		$this->query->addParameter($parameter);
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
			return $this->query;
		}	
		throw new \LogicException('Call insert() before');
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\Table|string $table
	 * @param \Change\Db\Query\Expressions\Column|string $column1 [optional]
	 * @param \Change\Db\Query\Expressions\Column|string $_ [optional]
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function insert($table = null)
	{
		if ($this->query)
		{
			$this->reset();
		}
		$insertQuery = new InsertQuery($this->dbProvider);
		$this->query = $insertQuery;
		
		$columns = func_get_args();
		array_shift($columns);
		
		if ($table)
		{
			if (is_string($table))
			{
				$table = $this->fragmentBuilder->table($table);
			}
			
			if ($table instanceof \Change\Db\Query\Expressions\Table)
			{
				$insertClause = new \Change\Db\Query\Clauses\InsertClause($table);
				$insertQuery->setInsertClause($insertClause);
			}
		}
				
		if (is_array($columns))
		{
			foreach ($columns as $column)
			{
				$this->addColumn($column);
			}
		}

		return $this;
	}
	
	/**
	 * Add a columns to the existing insert clause
	 *
	 * @api
	 * @param \Change\Db\Query\Expressions\Column|string $column1 [optional]
	 * @param \Change\Db\Query\Expressions\Column|string $_ [optional]
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function addColumns()
	{
		foreach (func_get_args() as $column)
		{
			$this->addColumn($column);
		}
		return $this;
	}
	
	/**
	 * Add a column  to the existing insert clause
	 *
	 * @api
	 * @param \Change\Db\Query\Expressions\Column|string $column
	 * @throws \LogicException
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function addColumn($column)
	{
		if (is_string($column))
		{
			$column = $this->fragmentBuilder->column($column);
		}
		$insertClause = $this->insertQuery()->getInsertClause();
		if ($insertClause === null)
		{
			$insertClause = new \Change\Db\Query\Clauses\InsertClause();
			$this->insertQuery()->setInsertClause($insertClause);
		}
		$insertClause->addColumn($column);
		return $this;
	}
	
	/**
	 * Add a values to the existing insert clause
	 *
	 * @api
	 * @param \Change\Db\Query\Expressions\AbstractExpression|string $column1 [optional]
	 * @param \Change\Db\Query\Expressions\AbstractExpression|string $_ [optional]
	 * @throws \LogicException
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function addValues()
	{
		foreach (func_get_args() as $expression)
		{
			$this->addValue($expression);
		}
		return $this;
	}
	
	/**
	 * Add a column  to the existing insert clause
	 *
	 * @api
	 * @param \Change\Db\Query\Expressions\AbstractExpression|string $expression
	 * @throws \LogicException
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function addValue($expression)
	{
		if (is_string($expression))
		{
			$expression = $this->fragmentBuilder->string($expression);
		}
		$valuesClause = $this->insertQuery()->getValuesClause();
		if ($valuesClause === null)
		{
			$valuesClause = new \Change\Db\Query\Clauses\ValuesClause();
			$this->insertQuery()->setValuesClause($valuesClause);
		}
		$valuesClause->addValue($expression);
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
			return $this->query;
		}
	
		throw new \LogicException('Call update() before');
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\Table|string $table
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function update($table = null)
	{
		if ($this->query)
		{
			$this->reset();
		}
		$this->query = new UpdateQuery($this->dbProvider);

		if ($table)
		{
			if (is_string($table))
			{
				$table = $this->fragmentBuilder->table($table);
			}
			if ($table instanceof \Change\Db\Query\Expressions\Table)
			{
				$updateClause = new \Change\Db\Query\Clauses\UpdateClause($table);
				$this->updateQuery()->setUpdateClause($updateClause);
			}
		}
		return $this;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\Column|string $column
	 * @param \Change\Db\Query\Expressions\AbstractExpression|string $expression
	 * @throws \LogicException
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function assign($column, $expression)
	{
		if (is_string($column))
		{
			$column = $this->fragmentBuilder->column($column);
		}
		if (is_string($expression))
		{
			$expression = $this->fragmentBuilder->string($expression);
		}
		$setClause = $this->updateQuery()->getSetClause();
		if ($setClause === null)
		{
			$setClause = new \Change\Db\Query\Clauses\SetClause();
			$this->updateQuery()->setSetClause($setClause);
		}
		$setClause->addSet($this->fragmentBuilder->assignment($column, $expression));
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
			return $this->query;
		}
	
		throw new \LogicException('Call delete() before');
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\Table|string $table
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function delete($table = null)
	{
		if ($this->query)
		{
			$this->reset();
		}
		$this->query = new DeleteQuery($this->dbProvider);

		if ($table)
		{
			if (is_string($table))
			{
				$table = $this->fragmentBuilder->table($table);
			}
			if ($table instanceof \Change\Db\Query\Expressions\Table)
			{
				$this->deleteQuery()->setDeleteClause(new \Change\Db\Query\Clauses\DeleteClause());
				$fromClause = new \Change\Db\Query\Clauses\FromClause($table);
				$this->deleteQuery()->setFromClause($fromClause);
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @param \Change\Db\Query\Predicates\InterfacePredicate $predicate
	 * @throws \LogicException
	 * @return \Change\Db\Query\StatementBuilder
	 */
	public function where(\Change\Db\Query\Predicates\InterfacePredicate $predicate)
	{
		$validQuery = $this->query;
		if ($validQuery instanceof UpdateQuery || $validQuery instanceof DeleteQuery)
		{
			$whereClause = new \Change\Db\Query\Clauses\WhereClause($predicate);
			$validQuery->setWhereClause($whereClause);
			return $this;
		}
		throw new \LogicException('Call update() or delete() before');
	}
}