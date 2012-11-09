<?php

namespace Change\Db\Query\Expressions;

class SubQuery extends AbstractExpression
{
	/**
	 * @var \Change\Db\Query\SelectQuery
	 */
	protected $subQuery;
	
	public function __construct(\Change\Db\Query\SelectQuery $subQuery = null)
	{
		$this->subQuery = $subQuery;
	}
	
	/**
	 * @return \Change\Db\Query\SelectQuery
	 */
	public function getSubQuery()
	{
		return $this->subQuery;
	}
	
	/**
	 * @param \Change\Db\Query\SelectQuery $subQuery        	
	 */
	public function setSubQuery($subQuery)
	{
		$this->subQuery = $subQuery;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return '(' . $this->getSubQuery()->toSQL92String() . ')';
	}
}