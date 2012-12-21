<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\BinaryOperation
 */
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
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $lhs
	 * @param \Change\Db\Query\Expressions\AbstractExpression $rhs
	 * @param string $operator
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $lhs = null, \Change\Db\Query\Expressions\AbstractExpression $rhs = null, $operator = null)
	{
		if ($lhs) {$this->setLeftHandExpression($lhs);}
		if ($rhs) {$this->setRightHandExpression($rhs);}
		$this->setOperator($operator);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression|null
	 */
	public function getLeftHandExpression()
	{
		return $this->leftHandExpression;
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression|null
	 */
	public function getRightHandExpression()
	{
		return $this->rightHandExpression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $leftHandExpression
	 */
	public function setLeftHandExpression(\Change\Db\Query\Expressions\AbstractExpression $leftHandExpression)
	{
		$this->leftHandExpression = $leftHandExpression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $rightHandExpression
	 */
	public function setRightHandExpression(\Change\Db\Query\Expressions\AbstractExpression $rightHandExpression)
	{
		$this->rightHandExpression = $rightHandExpression;
	}
	
	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->getLeftHandExpression() === null || $this->getRightHandExpression() === null)
		{
			throw new \RuntimeException('LeftHandExpression and RightHandExpression can not be null');
		}
		return $this->getLeftHandExpression()->toSQL92String() . ' ' . $this->getOperator() . ' ' . $this->getRightHandExpression()->toSQL92String();
	}
}