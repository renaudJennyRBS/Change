<?php

namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\AbstractExpression;
use Change\Db\Query\Expressions\BinaryOperation;

class Like extends BinaryOperation implements InterfacePredicate
{
	const ANYWHERE = 0;
	const BEGIN = 1;
	const END = 2;
	const EXACT = 3;
	
	/**
	 * @var integer
	 */
	protected $matchMode;
	
	/**
	 * @var boolean
	 */
	protected $caseSensitive;
	
	/**
	 * @param AbstractExpression $lhs
	 * @param AbstractExpression $rhs
	 * @param integer $matchMode
	 * @param boolean $caseSensitive
	 */
	public function __construct(AbstractExpression $lhs, AbstractExpression $rhs,  $matchMode = self::ANYWHERE, $caseSensitive = false)
	{
		$this->setLeftHandExpression($lhs);
		$this->setRightHandExpression($rhs);
		$this->matchMode = $matchMode;
		$this->caseSensitive = $caseSensitive;
		if ($this->caseSensitive)
		{
			$this->setOperator('LIKE BINARY');
		}
		else
		{
			$this->setOperator('LIKE');
		}
	}
	
	/**
	 * @return string
	 */
	public function pseudoQueryString()
	{
		switch ($this->matchMode)
		{
			case self::BEGIN;
				$rhs = "%s%%";
				break;
			case self::END;
				$rhs = "%%%s";
				break;
			case self::ANYWHERE;
				$rhs = "%%%s%%";
				break;
			default:
				$rhs = "%s";
		}
		$rhs = sprintf($rhs, $this->getRightHandExpression()->pseudoQueryString());
		return $this->getLeftHandExpression()->pseudoQueryString(). ' '  . $this->getOperator() . ' ' . $rhs;
	}
}