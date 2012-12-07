<?php

namespace Change\Db\Query\Expressions;

class BinaryOperation extends AbstractOperation
{
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $leftHandExpression;
	
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $rightHandExpression;
	
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $lhs = null,\Change\Db\Query\Expressions\AbstractExpression $rhs = null, $operator = null)
	{
		$this->setLeftHandExpression($lhs);
		$this->setRightHandExpression($rhs);
		$this->setOperator($operator);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getLeftHandExpression()
	{
		return $this->leftHandExpression;
	}

	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getRightHandExpression()
	{
		return $this->rightHandExpression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $leftHandExpression
	 */
	public function setLeftHandExpression($leftHandExpression)
	{
		$this->leftHandExpression = $leftHandExpression;
	}

	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $rightHandExpression
	 */
	public function setRightHandExpression($rightHandExpression)
	{
		$this->rightHandExpression = $rightHandExpression;
	}

	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return $this->getLeftHandExpression()->toSQL92String(). ' '  . $this->getOperator() . ' ' . $this->getRightHandExpression()->toSQL92String();
	}
}