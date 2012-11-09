<?php

namespace Change\Db\Query\Expressions;

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
	 * @param string | \Change\Db\Query\Expressions\Identifier $columnName
	 * @param \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Identifier $tableOrIdentifier
	 */
	public function __construct($columnName = null, $tableOrIdentifier = null)
	{
		$this->tableOrIdentifier = $tableOrIdentifier;
		if (is_string($columnName))
		{
			$columnName = new \Change\Db\Query\Expressions\Identifier(array($columnName));
		}
		$this->columnName = $columnName;
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
	 * @param \Change\Db\Query\Expressions\Table | \Change\Db\Query\Expressions\Identifier
	 */
	public function setTableOrIdentifier($tableOrIdentifier)
	{
		$this->tableOrIdentifier = $tableOrIdentifier;
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
		$table = null;
		if ($tableOrIdentifier)
		{
			$table = $tableOrIdentifier->toSQL92String();
		}
		return \Change\Stdlib\String::isEmpty($table) ? $columnName : $table . '.' . $columnName; 
	}
}