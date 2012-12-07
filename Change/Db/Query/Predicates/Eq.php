<?php
namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\AbstractExpression;
use Change\Db\Query\Expressions\BinaryOperation;

/**
 * @name \Change\Db\Query\Predicates\Eq
 */
class Eq extends BinaryOperation implements InterfacePredicate
{
	/**
	 * @param AbstractExpression $lhs
	 * @param AbstractExpression $rhs
	 */
	public function __construct(AbstractExpression $lhs, AbstractExpression $rhs)
	{
		$this->setLeftHandExpression($lhs);
		$this->setRightHandExpression($rhs);
		$this->setOperator('=');
	}
}