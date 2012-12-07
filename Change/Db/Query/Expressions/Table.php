<?php

namespace Change\Db\Query\Expressions;

class Table extends AbstractExpression
{
	/**
	 * @var string
	 */
	protected $database;
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var string
	 */
	protected $alias;
	
	/**
	 * @param string $tableName
	 * @param string $alias
	 */
	public function __construct($name, $database = null)
	{
		$this->setName($name);
		$this->setDatabase($database);
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $tableName
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return string
	 */
	public function getDatabase()
	{
		return $this->database;
	}

	/**
	 * @param string $database
	 */
	public function setDatabase($database)
	{
		$this->database = $database;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$identifierParts = array();
		$dbName = $this->getDatabase();
		$tableName = $this->getName();
		if (!\Change\Stdlib\String::isEmpty($dbName))
		{
			$identifierParts[] = '"' . $dbName . '"';
		}
		$identifierParts[] = '"' . $tableName . '"';
		return implode('.', $identifierParts);
	}
}