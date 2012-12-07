<?php
namespace Change\Db\Query\Predicates;

/**
 * @name \Change\Db\Query\Predicates\Conjunction
 */
class Conjunction extends \Change\Db\Query\Expressions\AbstractExpression implements InterfacePredicate
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
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}
	
	/**
	 * @param array $arguments
	 */
	public function setArguments($arguments)
	{
		$this->arguments = $arguments;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return '(' . implode(' AND ', array_map(function (\Change\Db\Query\Expressions\AbstractExpression $item) {
			return $item->toSQL92String();
		}, $this->arguments)) . ')';
	}
}