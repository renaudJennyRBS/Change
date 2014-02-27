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
 * @name \Change\Db\Query\Expressions\Join
 * @api
 */
class Join extends AbstractExpression
{
	const CROSS_JOIN = 1;
	const INNER_JOIN = 2;
	const LEFT_OUTER_JOIN = 4;
	const RIGHT_OUTER_JOIN = 8;
	const FULL_OUTER_JOIN = 16;
	
	/**
	 * @var integer
	 */
	protected $joinType;
	
	/**
	 * @var AbstractExpression
	 */
	protected $tableExpression;
	
	/**
	 * @var AbstractExpression
	 */
	protected $specification;
	
	/**
	 * @param AbstractExpression $tableExpression
	 * @param integer $type
	 * @param AbstractExpression $specification
	 */
	public function __construct(AbstractExpression $tableExpression, $type = self::CROSS_JOIN, $specification = null)
	{
		$this->setTableExpression($tableExpression);
		$this->setType($type);
		$this->setSpecification($specification);
	}
	
	/**
	 * @return AbstractExpression
	 */
	public function getSpecification()
	{
		return $this->specification;
	}
	
	/**
	 * @param AbstractExpression $joinSpecification
	 */
	public function setSpecification(AbstractExpression $joinSpecification = null)
	{
		$this->specification = $joinSpecification;
	}
	
	/**
	 * @return integer
	 */
	public function getType()
	{
		return $this->joinType;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @param integer $joinType
	 */
	public function setType($joinType)
	{
		switch ($joinType) 
		{
			case self::CROSS_JOIN:
			case self::INNER_JOIN:
			case self::LEFT_OUTER_JOIN:
			case self::RIGHT_OUTER_JOIN:
			case self::FULL_OUTER_JOIN:
				$this->joinType = $joinType;
				return;
		}
		throw new \InvalidArgumentException('Argument 1 must be a valid const', 42027);
	}
	
	/**
	 * Is it a qualified join (ie not a cross join)
	 *
	 * @api
	 * @return boolean
	 */
	public function isQualified()
	{
		return $this->getType() != self::CROSS_JOIN;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isOuterJoin()
	{
		return $this->getType() > self::INNER_JOIN;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isInnerJoin()
	{
		return $this->getType() == self::INNER_JOIN;
	}
	
	/**
	 * @api
	 * @return boolean
	 */
	public function isNatural()
	{
		return $this->specification === null;
	}
	
	/**
	 * @return AbstractExpression
	 */
	public function getTableExpression()
	{
		return $this->tableExpression;
	}
	
	/**
	 * @param AbstractExpression $joinedTable
	 */
	public function setTableExpression(AbstractExpression $joinedTable)
	{
		$this->tableExpression = $joinedTable;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$joinedTable = $this->getTableExpression();
		$parts = array();
		if ($this->isNatural())
		{
			$parts[] = 'NATURAL';
		}
		if ($this->isQualified())
		{
			switch ($this->getType())
			{
				case self::LEFT_OUTER_JOIN :
					$parts[] = 'LEFT OUTER JOIN';
					break;
				case self::RIGHT_OUTER_JOIN :
					$parts[] = 'RIGHT OUTER JOIN';
					break;
				case self::FULL_OUTER_JOIN :
					$parts[] = 'FULL OUTER JOIN';
					break;
				case self::INNER_JOIN :
				default :
					$parts[] = 'INNER JOIN';
					break;
			}
		}
		else
		{
			$parts[] = 'CROSS JOIN';
		}
		$parts[] = $joinedTable->toSQL92String();
		if (!$this->isNatural())
		{
			$joinSpecification = $this->getSpecification();
			$parts[] = $joinSpecification->toSQL92String();
		}
		return implode(' ', $parts);
	}
}