<?php
namespace Change\Db\Query\Clauses;

/**
 * @name \Change\Db\Query\Clauses\UpdateClause
 * @api
 */
class UpdateClause extends AbstractClause
{
	/**
	 * @var \Change\Db\Query\Expressions\Table
	 */
	protected $table;
	
	/**
	 * @param \Change\Db\Query\Expressions\Table $table
	 */
	public function __construct(\Change\Db\Query\Expressions\Table $table = null)
	{
		$this->setName('UPDATE');
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
		return 'UPDATE ' . $this->getTable()->toSQL92String();
	}
}
