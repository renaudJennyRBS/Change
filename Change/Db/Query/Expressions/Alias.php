<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Alias
 */
class Alias extends BinaryOperation
{
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $lhs
	 * @param \Change\Db\Query\Expressions\AbstractExpression $rhs
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $lhs = null, \Change\Db\Query\Expressions\AbstractExpression $rhs = null)
	{
		parent::__construct($lhs, $rhs, 'AS');
	}
}