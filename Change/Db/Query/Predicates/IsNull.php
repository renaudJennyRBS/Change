<?php
namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\UnaryOperation;

/**
 * @name \Change\Db\Query\Predicates\IsNull
 */
class IsNull extends UnaryOperation implements InterfacePredicate
{
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->setOperator('IS NULL');
		$this->setExpression($expression);
	}
	
	/**
	 * @return string
	 */
	public function pseudoQueryString()
	{
		return $this->getExpression()->pseudoQueryString() . ' ' . $this->getOperator();
	}
}
