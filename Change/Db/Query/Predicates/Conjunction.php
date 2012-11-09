<?php

namespace Change\Db\Query\Predicates;

class Conjunction extends \Change\Db\Query\Expressions\AbstractExpression implements InterfacePredicate
{
	protected $arguments;
	
	public function __construct()
	{
		$this->arguments = func_get_args();	
	}
	
	/**
	 * @return multitype:
	 */
	public function getArguments()
	{
		return $this->arguments;
	}

	/**
	 * @param multitype: $arguments
	 */
	public function setArguments($arguments)
	{
		$this->arguments = $arguments;
	}

	/**
	 */
	public function toSQL92String()
	{
		return '(' . implode(' AND ', array_map(function (\Change\Db\Query\Expressions\AbstractExpression $item)
		{
			return $item->toSQL92String();
		}, $this->arguments)) . ')';
	}
}