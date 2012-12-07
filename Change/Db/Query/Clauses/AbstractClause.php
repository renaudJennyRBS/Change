<?php
namespace Change\Db\Query\Clauses;

use Change\Db\Query\InterfaceSQLFragment;

/**
 * @name \Change\Db\Query\Clauses\AbstractClause
 */
abstract class AbstractClause implements \Change\Db\Query\InterfaceSQLFragment
{
	/**
	 * SQL Specific vendor options.
	 * 
	 * @api 
	 * @var array
	 */
	protected $options;
	
	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}	
	
	/**
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
}