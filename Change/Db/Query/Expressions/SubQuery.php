<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Raw
 */
class SubQuery extends AbstractExpression
{
	/**
	 * @var \Change\Db\Query\SelectQuery
	 */
	protected $subQuery;
	
	/**
	 * @param \Change\Db\Query\SelectQuery $subQuery
	 */
	public function __construct(\Change\Db\Query\SelectQuery $subQuery)
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
	public function setSubQuery(\Change\Db\Query\SelectQuery $subQuery)
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