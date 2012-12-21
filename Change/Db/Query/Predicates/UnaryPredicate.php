<?php
namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\UnaryOperation;

/**
 * @name \Change\Db\Query\Predicates\UnaryOperation
 */
class UnaryPredicate extends UnaryOperation implements InterfacePredicate
{
	const NOT = 'NOT';
	const ISNULL = 'IS NULL';
	const ISNOTNULL = 'IS NOT NULL';
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->getOperator() === self::ISNULL || $this->getOperator() === self::ISNOTNULL)
		{
			return $this->getExpression()->toSQL92String() . ' ' .$this->getOperator();
		}
		return parent::toSQL92String();
	}
}
