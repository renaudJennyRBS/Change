<?php
namespace Change\Db\Query\Clauses;

/**
 * @name \Change\Db\Query\Clauses\FromClause
 */
class FromClause extends AbstractClause
{
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $tableExpression;
	
	/**
	 * @var \Change\Db\Query\Expressions\Join
	 */
	protected $joins = array();
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $tableExpression
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $tableExpression = null)
	{
		$this->setTableExpression($tableExpression);
	}
	
	/**
	 * @return \Change\Db\Query\Objects\Table
	 */
	public function getTableExpression()
	{
		return $this->tableExpression;
	}
	
	/**
	 * @param \Change\Db\Query\Table $tableExpression
	 */
	public function setTableExpression($tableExpression)
	{
		$this->tableExpression = $tableExpression;
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\Join
	 */
	public function getJoins()
	{
		return $this->joins;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Join $joins
	 */
	public function setJoins($joins)
	{
		$this->joins = $joins;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Join $join
	 */
	public function addJoin(\Change\Db\Query\Expressions\Join $join)
	{
		$this->joins[] = $join;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$from = 'FROM ' . $this->getTableExpression()->toSQL92String();
		$joins = implode(' ', array_map(function (\Change\Db\Query\Expressions\Join $join) {
			return $join->toSQL92String();
		}, $this->getJoins()));
		return \Change\Stdlib\String::isEmpty($joins) ? $from : $from . ' ' . $joins;
	}
}
