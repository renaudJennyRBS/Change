<?php
namespace Change\Db\Query\Expressions;

use Change\Db\Query\InterfaceSQLFragment;

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
	 * @var interger
	 */
	protected $joinType;
	
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $tableExpression;
	
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $specification;
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $tableExpression
	 * @param interger $type
	 * @param \Change\Db\Query\Expressions\AbstractExpression $specification
	 */
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
	public function setSpecification(AbstractExpression $joinSpecification = null)
	{
		$this->specification = $joinSpecification;
	}
	
	/**
	 * @return interger
	 */
	public function getType()
	{
		return $this->joinType;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @param interger $joinType
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
		throw new \InvalidArgumentException('Argument 1 must be a valid const');
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
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getTableExpression()
	{
		return $this->tableExpression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $joinedTable
	 */
	public function setTableExpression(\Change\Db\Query\Expressions\AbstractExpression $joinedTable)
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