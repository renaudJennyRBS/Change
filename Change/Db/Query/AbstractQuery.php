<?php

namespace Change\Db\Query;

abstract class AbstractQuery implements \Change\Db\Query\InterfaceSQLFragment
{
	/**
	 * DB Provider instance the query will be executed with 
	 * 
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;
	
	/**
	 * @var array
	 */
	protected $parameters = array();
	
	/**
	 * @var array
	 */
	protected $parametersValue;
	
	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function __construct(\Change\Db\DbProvider $dbProvider)
	{
		$this->setDbProvider($dbProvider);
	}
	
	/**
	 * Get the query's parameters list
	 * 
	 * @api
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}
	
	/**
	 * Set the query's parameters list
	 * 
	 * @api
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
	
	/**
	 * Declare a new query parameter 
	 * 
	 * @api
	 * @param string $parameter
	 * @return \Change\Db\Query\AbstractQuery
	 */
	public function addParameter($parameter)
	{
		$this->parameters[] = $parameter;
		return $this;
	}
	
	/**
	 * Bind a value to an existing parameter
	 * 
	 * @api
	 * @param string $parameterName
	 * @param string $value
	 */
	public function bindParameter($parameterName, $value)
	{
		if (!isset($this->parameters[$parameterName]))
		{
			throw new \RuntimeException('Parameter ' . $parameterName . ' does not exist');
		}
		$this->parametersValue[$parameterName] = $value;
		return $this;
	}
	
	/**
	 * Convert query to a SQL string
	 * 
	 * @param callable $callable
	 * @return string
	 */
	public function toSQLString($callable = null)
	{
		if ($callable)
		{
			return call_user_func($callable, $this);
		}
		return $this->toSQL92String();
	}
	
	/**
	 * Get the provider the query is bound to
	 * 
	 * @api 
	 * @return \Change\Db\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * Set the provider the query is bound to
	 * 
	 * @api
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * SQL-92 representation of the query (mostly for tests)
	 */
	abstract public function toSQL92String();
}