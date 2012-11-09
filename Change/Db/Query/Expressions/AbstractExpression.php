<?php

namespace Change\Db\Query\Expressions;

abstract class AbstractExpression implements \Change\Db\Query\InterfaceSQLFragment
{
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
	
	abstract public function toSQL92String();
}