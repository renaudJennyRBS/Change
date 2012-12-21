<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\AbstractOperation
 */
abstract class AbstractOperation extends AbstractExpression
{	
	/**
	 * @var string
	 */
	protected $operator;
	
	/**
	 * @return string
	 */
	public function getOperator()
	{
		return $this->operator;
	}
	
	/**
	 * @param string $operator
	 */
	public function setOperator($operator)
	{
		$this->operator = $operator;
	}
}