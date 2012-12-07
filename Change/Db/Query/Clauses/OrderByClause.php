<?php
namespace Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\AbstractClause;

/**
 * @name \Change\Db\Query\Clauses\OrderByClause
 */
class OrderByClause extends AbstractClause
{
	/**
	 * @var Change\Db\Query\Expressions\ExpressionList
	 */
	protected $expressionList;
	
	/**
	 * @param \Change\Db\Query\Expressions\ExpressionList $expressionList
	 */
	public function __construct(\Change\Db\Query\Expressions\ExpressionList $expressionList = null)
	{
		$this->setExpressionList($expressionList);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function getExpressionList()
	{
		return $this->expressionList;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\ExpressionList $expressionList
	 */
	public function setExpressionList($expressionList)
	{
		$this->expressionList = $expressionList;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function addExpression($expression)
	{
		$list = $this->getExpressionList();
		if ($list === null)
		{
			$list = new \Change\Db\Query\Expressions\ExpressionList();
			$this->setExpressionList($list);
		}
		$list->add($expression);
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return 'ORDER BY ' . $this->getExpressionList()->toSQL92String();
	}
}