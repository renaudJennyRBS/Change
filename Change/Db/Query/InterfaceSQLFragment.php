<?php

namespace Change\Db\Query;

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