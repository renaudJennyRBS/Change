<?php
namespace Change\Db\Query;

/**
 * @name \Change\Db\Query\InterfaceSQLFragment
 */
interface InterfaceSQLFragment
{
	/**
	 * @param callable $callable
	 * @return string
	 */
	public function toSQLString($callable = null);
	
	/**
	 * @return string
	 */
	public function toSQL92String();
}