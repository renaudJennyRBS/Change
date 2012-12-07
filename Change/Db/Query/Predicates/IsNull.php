<?php

namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\UnaryOperation;

class IsNull extends UnaryOperation implements InterfacePredicate
{
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
		return $this->getExpression()->pseudoQueryString(). ' '  . $this->getOperator();
	}
}
