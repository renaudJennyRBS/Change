<?php
namespace Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\AbstractClause;

/**
 * @name \Change\Db\Query\Clauses\ValuesClause
 */
class ValuesClause extends AbstractClause
{	
	/**	  
	 * @var \Change\Db\Query\Expressions\ExpressionList
	 */
	protected $valueList;
	
	/**
	 * @param array $values
	 */
	public function __construct(\Change\Db\Query\Expressions\ExpressionList $values = null)
	{
		$this->setName('VALUES');
		$this->setValuesList($values ? $values : new \Change\Db\Query\Expressions\ExpressionList());
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function getValuesList()
	{
		return $this->valueList;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\ExpressionList
	 */
	public function setValuesList(\Change\Db\Query\Expressions\ExpressionList $values)
	{
		$this->valueList = $values;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Clauses\ValuesClause
	 */
	public function addValue(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->valueList->add($expression);
		return $this;
	}
	
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if (!$this->getValuesList()->count())
		{
			throw new \RuntimeException('ValuesList can not be empty');
		}
	}
		
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		return 'VALUES ('. $this->getValuesList()->toSQL92String() . ')';
	}
}