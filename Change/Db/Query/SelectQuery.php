<?php
namespace Change\Db\Query;

/**
 * @name \Change\Db\Query\SelectQuery
 */
class SelectQuery extends \Change\Db\Query\AbstractQuery
{
	/**
	 * @var \Change\Db\Query\Clauses\SelectClause
	 */
	protected $selectClause;
	
	/**
	 * @return \Change\Db\Query\Clauses\SelectClause
	 */
	public function getSelectClause()
	{
		return $this->selectClause;
	}
	
	/**
	 * @param \Change\Db\Query\Clauses\SelectClause $selectClause
	 */
	public function setSelectClause($selectClause)
	{
		$this->selectClause = $selectClause;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return $this->selectClause->toSQL92String();
	}
}
