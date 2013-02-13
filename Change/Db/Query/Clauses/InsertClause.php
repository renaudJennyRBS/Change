<?php
namespace Change\Db\Query\Clauses;

/**
 * @name \Change\Db\Query\Clauses\InsertClause
 * @api
 */
class InsertClause extends AbstractClause
{
	/**
	 * @var \Change\Db\Query\Expressions\Table
	 */
	protected $table;
	
	/**
	 * @var \Change\Db\Query\Expressions\Column[]
	 */
	protected $columns = array();
	

	/**
	 * @param \Change\Db\Query\Expressions\Table $table
	 */
	public function __construct(\Change\Db\Query\Expressions\Table $table = null)
	{
		$this->setName('INSERT');
		if ($table)  {$this->setTable($table);}
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\Table|null
	 */
	public function getTable()
	{
		return $this->table;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Table $table
	 */
	public function setTable(\Change\Db\Query\Expressions\Table $table)
	{
		$this->table = $table;
	}

	/**
	 * @return \Change\Db\Query\Expressions\Column[]
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	/**
	 * @param \Change\Db\Query\Expressions\Column[] $columns
	 */
	public function setColumns(array $columns)
	{
		$this->columns = array();
		foreach ($columns as $column)
		{
			$this->addColumn($column);
		}
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Column $column
	 * @return \Change\Db\Query\Clauses\InsertClause
	 */
	public function addColumn(\Change\Db\Query\Expressions\Column $column)
	{
		$this->columns[] = $column;
		return $this;
	}
	
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if ($this->getTable() === null)
		{
			throw new \RuntimeException('Table can not be null');
		}
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{		
		$this->checkCompile();
		$insert = 'INSERT ' . $this->getTable()->toSQL92String();
		$columns = $this->getColumns();
		if (count($columns))
		{
			$insert .= ' (' . implode(', ', array_map(function (\Change\Db\Query\Expressions\Column $column) {
				return $column->toSQL92String();
			}, $columns)) . ')';
		}
		return $insert;
	}
}
