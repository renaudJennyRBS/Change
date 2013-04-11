<?php
namespace Change\Db\Query;

use Change\Db\DbProvider;

/**
 * @api
 * @name \Change\Db\Query\AbstractQuery
 */
abstract class AbstractQuery implements InterfaceSQLFragment
{
	/**
	 * DB Provider instance the query will be executed with.
	 * @var DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var string
	 */
	protected $cachedKey;

	/**
	 * @var string
	 */
	protected $cachedSql;

	/**
	 * SQL Specific vendor options.
	 * @api
	 * @var array
	 */
	protected $options;

	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @var array
	 */
	protected $parametersValue = array();

	/**
	 * @param DbProvider $dbProvider
	 * @param string $cachedKey
	 */
	public function __construct(DbProvider $dbProvider, $cachedKey = null)
	{
		$this->setDbProvider($dbProvider);
		$this->cachedKey = $cachedKey;
	}

	/**
	 * Get the query's parameters list.
	 * @api
	 * @return \Change\Db\Query\Expressions\Parameter[]
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Set the query's parameters list.
	 * @api
	 * @param \Change\Db\Query\Expressions\Parameter[] $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = array();
		if (is_array($parameters))
		{
			foreach ($parameters as $parameter)
			{
				$this->addParameter($parameter);
			}
		}
	}

	/**
	 * Declare a new query parameter.
	 * @api
	 * @throws \RuntimeException
	 * @param \Change\Db\Query\Expressions\Parameter $parameter
	 * @return \Change\Db\Query\AbstractQuery
	 */
	public function addParameter(\Change\Db\Query\Expressions\Parameter $parameter)
	{
		$parameterName = $parameter->getName();
		if (isset($this->parameters[$parameterName]))
		{
			throw new \RuntimeException('Parameter ' . $parameterName . ' already exist', 42000);
		}
		$this->parameters[$parameterName] = $parameter;
		return $this;
	}

	/**
	 * Bind a value to an existing parameter.
	 * @api
	 * @throws \RuntimeException
	 * @param string $parameterName
	 * @param mixed $value
	 * @return \Change\Db\Query\AbstractQuery
	 */
	public function bindParameter($parameterName, $value)
	{
		if (!isset($parameterName, $this->parameters))
		{
			throw new \RuntimeException('Parameter ' . $parameterName . ' does not exist', 42001);
		}
		$this->parametersValue[$parameterName] = $value;
		return $this;
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @param string $parameterName
	 * @return mixed
	 */
	public function getParameterValue($parameterName)
	{
		if (!isset($parameterName, $this->parameters))
		{
			throw new \RuntimeException('Parameter ' . $parameterName . ' does not exist', 42001);
		}
		return isset($this->parametersValue[$parameterName]) ? $this->parametersValue[$parameterName] : null;
	}

	/**
	 * @api
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @api
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * Get the provider the query is bound to.
	 * @api
	 * @return DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * Set the provider the query is bound to.
	 * @api
	 * @param DbProvider $dbProvider
	 */
	public function setDbProvider(DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @return string
	 */
	public function getCachedKey()
	{
		return $this->cachedKey;
	}

	/**
	 * @return string
	 */
	public function getCachedSql()
	{
		return $this->cachedSql;
	}

	/**
	 * @param string $cachedSql
	 */
	public function setCachedSql($cachedSql)
	{
		$this->cachedSql = $cachedSql;
	}

	/**
	 * SQL-92 representation of the query (mostly for tests).
	 * @return string
	 */
	abstract public function toSQL92String();
}