<?php

namespace Change\Db\Query\Expressions;

use Change\Db\Query\InterfaceSQLFragment;

class Join extends AbstractExpression
{
	const CROSS_JOIN = 1;
	const INNER_JOIN = 2;	
	const LEFT_OUTER_JOIN = 4;
	const RIGHT_OUTER_JOIN = 8;
	const FULL_OUTER_JOIN = 16;
	
	/**
	 * @var interger
	 */
	protected $joinType;
	
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $tableExpression;
	
	/**
	 * @var  \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $specification;

	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $tableExpression, $type = self::CROSS_JOIN, $specification = null)
	{
		$this->setTableExpression($tableExpression);
		$this->setType($type);
		$this->setSpecification($specification);
	}

	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getSpecification()
	{
		return $this->specification;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $joinSpecification
	 */
	public function setSpecification($joinSpecification)
	{
		$this->specification = $joinSpecification;
	}
	
	/**
	 * @return \Change\Db\Query\Objects\interger
	 */
	public function getType()
	{
		return $this->joinType;
	}
	
	/**
	 * @param \Change\Db\Query\Objects\interger $joinType
	 */
	public function setType($joinType)
	{
		$this->joinType = $joinType;
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
	 * @return \Change\Db\Query\Expressions\Table
	 */
	public function getTableExpression()
	{
		return $this->tableExpression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Table $joinedTable
	 */
	public function setTableExpression($joinedTable)
	{
		$this->tableExpression = $joinedTable;
	}

	/**
	 *
	 * @param unknown_type $callable        	
	 */
	public function toSQL92String()
	{
		$joinedTable = $this->getTableExpression();
		if (!$joinedTable)
		{
			throw new \RuntimeException('A joined table is required');
		}
		$parts =  array();
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
				default:
					$parts[] = 'INNER JOIN';
					break;
			}
		}
		else
		{
			$parts[] = 'CROSS JOIN';
		}
		$parts[] = $joinedTable->toSQLString();
		if (!$this->isNatural())
		{
			$joinSpecification = $this->getSpecification();
			$parts[] = $joinSpecification->toSQLString();
		}
		return implode(' ', $parts);
	}
}