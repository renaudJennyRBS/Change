<?php

namespace Change\Db\Query\Expressions;

class ExpressionList extends \Change\Db\Query\Expressions\AbstractExpression 
{
	/** 
	 * @var Change\Db\Query\Expressions\AbstractExpression[]
	 */
	protected $list;
	
	public function __construct($list = null)
	{
		$this->setList($list);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression[]
	 */
	public function getList()
	{
		return $this->list;
	}

	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression[] $list
	 */
	public function setList($list)
	{
		$this->list = $list;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function add(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->list[] = $expression;
		return $this;
	}
	
	/**
	 */
	public function toSQL92String()
	{
		return implode(', ', array_map(function(\Change\Db\Query\Expressions\AbstractExpression $item){
			return $item->toSQL92String();
		}, $this->getList()));
	}

}