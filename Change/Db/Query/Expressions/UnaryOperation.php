<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Table
 */
class UnaryOperation extends AbstractOperation
{
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $expression;
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @param string $operator
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $expression = null, $operator = null)
	{
		$this->expression = $expression;
		$this->operator = $operator;
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getExpression()
	{
		return $this->expression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function setExpression($expression)
	{
		$this->expression = $expression;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return $this->getOperator() . ' ' . $this->getExpression()->toSQL92String();
	}
}