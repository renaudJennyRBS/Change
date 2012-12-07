<?php
namespace Change\Db\Query\Predicates;

/**
 * @name \Change\Db\Query\Predicates\Disjunction
 */
class Disjunction extends \Change\Db\Query\Expressions\BinaryOperation implements InterfacePredicate
{
	/**
	 * @var array
	 */
	protected $arguments;
	
	/**
	 */
	public function __construct()
	{
		$this->arguments = func_get_args();
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return '(' . implode(' OR ', array_map(function (\Change\Db\Query\Expressions\AbstractExpression $item) {
			return $item->toSQL92String();
		}, $this->arguments)) . ')';
	}
}