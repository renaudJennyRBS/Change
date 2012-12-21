<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Column
 */
class Column extends AbstractExpression
{
	/**
	 * @var \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Identifier
	 */
	protected $tableOrIdentifier;
	
	/**
	 * @var \Change\Db\Query\Expressions\Identifier
	 */
	protected $columnName;
	
	/**
	 * @param \Change\Db\Query\Expressions\Identifier $columnName
	 * @param \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Identifier $tableOrIdentifier
	 */
	public function __construct(\Change\Db\Query\Expressions\Identifier $columnName, $tableOrIdentifier = null)
	{
		$this->setColumnName($columnName);
		$this->setTableOrIdentifier($tableOrIdentifier);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Identifie
	 */
	public function getTableOrIdentifier()
	{
		return $this->tableOrIdentifier;
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\Identifier
	 */
	public function getColumnName()
	{
		return $this->columnName;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @param \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Identifier | null
	 */
	public function setTableOrIdentifier($tableOrIdentifier = null)
	{
		if ($tableOrIdentifier === null || $tableOrIdentifier instanceof Table || $tableOrIdentifier instanceof Identifier)
		{
			$this->tableOrIdentifier = $tableOrIdentifier;
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 must be a Expressions\Table | Expressions\Identifier');
		}
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Identifier $columnName
	 */
	public function setColumnName(\Change\Db\Query\Expressions\Identifier $columnName)
	{
		$this->columnName = $columnName;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$columnName = $this->getColumnName()->toSQL92String();
		$tableOrIdentifier = $this->getTableOrIdentifier();
		$table =  ($tableOrIdentifier) ? $tableOrIdentifier->toSQL92String() : null;
		return \Change\Stdlib\String::isEmpty($table) ? $columnName : $table . '.' . $columnName;
	}
}