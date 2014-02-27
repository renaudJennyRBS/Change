<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\AbstractExpression;

/**
 * @name \Change\Db\Query\Predicates\In
 * @api
 */
class In extends BinaryPredicate
{

	/**
	 * @var boolean
	 */
	protected $not = false;

	/**
	 * @param AbstractExpression $lhs
	 * @param AbstractExpression $rhs
	 * @param boolean $not
	 */
	public function __construct(AbstractExpression $lhs = null, AbstractExpression $rhs = null, $not = false)
	{
		parent::__construct($lhs, $rhs, 'IN');
		$this->setNot($not);
	}

	/**
	 * @return boolean
	 */
	public function getNot()
	{
		return $this->not;
	}

	/**
	 * @param boolean $not
	 */
	public function setNot($not)
	{
		$this->not = ($not == true);
		$this->setOperator(($this->not) ? 'NOT IN' : 'IN');
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		parent::checkCompile();
		$rhe = $this->getRightHandExpression();
		if ($rhe instanceof \Change\Db\Query\Expressions\ExpressionList)
		{
			if ($rhe->count() == 0)
			{
				throw new \RuntimeException('Right Hand Expression must be a ExpressionList with one element or more', 42035);
			}
			return;
		}
		elseif ($rhe instanceof  \Change\Db\Query\Expressions\SubQuery)
		{
			$subQuery = $rhe->getSubQuery();
			$subQuery->checkCompile();
			if ($subQuery->getSelectClause()->getSelectList()->count() != 1)
			{
				throw new \RuntimeException('Right Hand Expression must be a SubQuery with one element in select clause', 42036);
			}
			return;
		}
		throw new \RuntimeException('Right Hand Expression must be a SubQuery or ExpressionList', 42037);
	}

	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getCompletedRightHandExpression()
	{
		$rhe = $this->getRightHandExpression();
		if (!($rhe instanceof \Change\Db\Query\Expressions\SubQuery))
		{
			$rhe = new \Change\Db\Query\Expressions\Parentheses($rhe);
		}
		return $rhe;
	}

	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		return $this->getLeftHandExpression()->toSQL92String() . ' ' . $this->getOperator() . ' ' . $this
			->getCompletedRightHandExpression()->toSQL92String();
	}
}