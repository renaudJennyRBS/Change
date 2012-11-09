<?php

namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\AbstractExpression;
use Change\Db\Query\Expressions\BinaryOperation;

class Eq extends BinaryOperation implements InterfacePredicate
{
	public function __construct(AbstractExpression $lhs, AbstractExpression $rhs)
	{
		$this->setLeftHandExpression($lhs);
		$this->setRightHandExpression($rhs);
		$this->setOperator('=');
	}
}