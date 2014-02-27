<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\BinaryOperation
 * @api
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
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if (!($this->getLeftHandExpression() instanceof \Change\Db\Query\InterfaceSQLFragment))
		{
			throw new \RuntimeException('Invalid Left Hand Expression', 42030);
		}
		elseif (!($this->getRightHandExpression() instanceof \Change\Db\Query\InterfaceSQLFragment))
		{
			throw new \RuntimeException('Invalid Right Hand Expression', 42031);
		}
	}
	
	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		return $this->getLeftHandExpression()->toSQL92String() . ' ' . $this->getOperator() . ' ' . $this->getRightHandExpression()->toSQL92String();
	}
}