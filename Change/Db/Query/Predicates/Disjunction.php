<?php

namespace Change\Db\Query\Predicates;

class Disjunction extends \Change\Db\Query\Expressions\BinaryOperation implements InterfacePredicate
{
	protected $arguments;
	
	public function __construct()
	{
		$this->arguments = func_get_args();	
	}
	
	/**
	 */
	public function toSQL92String()
	{
		return '(' . implode(' OR ', array_map(function (\Change\Db\Query\Expressions\AbstractExpression $item)
		{
			return $item->toSQL92String();
		}, $this->arguments)) . ')';
	}
}