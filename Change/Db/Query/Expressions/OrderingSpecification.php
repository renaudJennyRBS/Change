<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\OrderingSpecification
 */
class OrderingSpecification extends UnaryOperation
{
	const ASC = 'ASC';
	const DESC = 'DESC';
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @param string $directionOperator
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $expression, $directionOperator = self::ASC)
	{
		parent::__construct($expression, $directionOperator);
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return $this->getExpression()->toSQL92String() . ' ' . $this->getOperator();
	}
}