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
		$this->setName('FROM');
		if ($tableExpression) {$this->setTableExpression($tableExpression);}
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\Table|null
	 */
	public function getTableExpression()
	{
		return $this->tableExpression;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Table $tableExpression
	 */
	public function setTableExpression(\Change\Db\Query\Expressions\AbstractExpression $tableExpression)
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
	 * @param \Change\Db\Query\Expressions\Join[] $joins
	 */
	public function setJoins(array $joins)
	{
		$this->joins = array_map(function (\Change\Db\Query\Expressions\Join $join) {return $join;}, $joins);
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Join $join
	 * @return \Change\Db\Query\Clauses\FromClause
	 */
	public function addJoin(\Change\Db\Query\Expressions\Join $join)
	{
		$this->joins[] = $join;
		return $this;
	}
	
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if ($this->getTableExpression() === null)
		{
			throw new \RuntimeException('TableExpression can not be null');
		}
	}
	
	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{	
		$this->checkCompile();	
		$from = 'FROM ' . $this->getTableExpression()->toSQL92String();
		if (count($this->getJoins()))
		{
			$from .= ' ' . implode(' ', array_map(function (\Change\Db\Query\Expressions\Join $join) {
				return $join->toSQL92String();
			}, $this->getJoins()));
		}
		return $from;
	}
}
