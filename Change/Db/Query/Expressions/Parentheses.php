<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Parentheses
 */
class Parentheses extends \Change\Db\Query\Expressions\AbstractExpression
{
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $expression;
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->setExpression($expression);
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
	public function setExpression(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->expression = $expression;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return '( ' . $this->getExpression()->toSQL92String() . ')';
	}
}