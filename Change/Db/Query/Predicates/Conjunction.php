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
	 * @param \Change\Db\Query\InterfaceSQLFragment $_
	 */
	public function __construct()
	{
		$this->setArguments(func_get_args());
	}
	
	/**
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}
	
	/**
	 * @param \Change\Db\Query\InterfaceSQLFragment[] $arguments
	 */
	public function setArguments(array $arguments)
	{
		$this->arguments = array_map(function (\Change\Db\Query\InterfaceSQLFragment $item) {return $item;}, $arguments);
	}
	
	/**
	 * @param \Change\Db\Query\InterfaceSQLFragment[] $arguments
	 * @return \Change\Db\Query\Predicates\Conjunction
	 */
	public function addArgument(\Change\Db\Query\InterfaceSQLFragment $argument)
	{
		$this->arguments[] = $argument;
		return $this;
	}
	
	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		if (!count($this->arguments))
		{
			throw new \RuntimeException('Arguments can not be empty');
		}
		return '(' . implode(' AND ', array_map(function(\Change\Db\Query\InterfaceSQLFragment $item) {
			return $item->toSQL92String();
		}, $this->arguments)) . ')';
	}
}