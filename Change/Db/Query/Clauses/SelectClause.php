<?php
namespace Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\AbstractClause;

/**
 * @name \Change\Db\Query\Clauses\SelectClause
 */
class SelectClause extends AbstractClause
{
	const QUANTIFIER_DISTINCT = 'DISTINCT';
	const QUANTIFIER_ALL = 'ALL';
	
	/**
	 * @var string
	 */
	protected $quantifier;
	
	/**	  
	 * @var \Change\Db\Query\Expressions\ExpressionList
	 */
	protected $selectList;
	
	/**
	 * @param \Change\Db\Query\Expressions\ExpressionList $list
	 */
	public function __construct(Change\Db\Query\Expressions\ExpressionList $list = null)
	{
		if ($list) $this->setSelectList($list);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function getSelectList()
	{
		return $this->selectList;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\ExpressionList $expression
	 */
	public function setSelectList($expression)
	{
		$this->selectList = $expression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function addSelect($expression)
	{
		if ($this->selectList === null)
		{
			$this->selectList = new \Change\Db\Query\Expressions\ExpressionList();
		}
		$this->selectList->add($expression);
	}
	
	/**
	 * @return string
	 */
	public function getQuantifier()
	{
		return $this->quantifier;
	}
	
	/**
	 * @param string $quantifier
	 */
	public function setQuantifier($quantifier)
	{
		$this->quantifier = $quantifier;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$parts = array('SELECT');
		if ($this->getQuantifier() === self::QUANTIFIER_DISTINCT)
		{
			$parts[] = self::QUANTIFIER_DISTINCT;
		}
		$selectList = $this->getSelectList();
		if ($selectList === null)
		{
			$selectList = new \Change\Db\Query\Expressions\AllColumns();
		}
		
		$parts[] = $selectList->toSQL92String();
		return implode(' ', $parts);
	}
}