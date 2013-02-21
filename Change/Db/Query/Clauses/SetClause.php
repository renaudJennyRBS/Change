<?php
namespace Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\AbstractClause;

/**
 * @name \Change\Db\Query\Clauses\SetClause
 * @api
 */
class SetClause extends AbstractClause
{	
	/**	  
	 * @var \Change\Db\Query\Expressions\ExpressionList
	 */
	protected $setList;
	
	/**
	 * @param array $setList
	 */
	public function __construct(\Change\Db\Query\Expressions\ExpressionList $setList = null)
	{
		$this->setName('SET');
		$this->setSetList($setList ? $setList : new \Change\Db\Query\Expressions\ExpressionList());
	}
	
	/**
	 * @api
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function getSetList()
	{
		return $this->setList;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\ExpressionList
	 */
	public function setSetList(\Change\Db\Query\Expressions\ExpressionList $setList)
	{
		$this->setList = $setList;
	}
	
	/**
	 * @api
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Clauses\SetClause
	 */
	public function addSet(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->setList->add($expression);
		return $this;
	}
	
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if (!$this->getSetList()->count())
		{
			throw new \RuntimeException('Values can not be empty', 42028);
		}
	}
		
	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		return 'SET '. $this->getSetList()->toSQL92String();
	}
}