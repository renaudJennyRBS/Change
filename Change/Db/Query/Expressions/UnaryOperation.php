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
		if ($expression) {$this->setExpression($expression);}
		$this->setOperator($operator);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression|null
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
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->getExpression() === null)
		{
			throw new \RuntimeException('Expression can not be null', 42023);
		}
		return $this->getOperator() . ' ' . $this->getExpression()->toSQL92String();
	}
}