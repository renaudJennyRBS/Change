<?php

namespace Change\Db\Query\Expressions;

abstract class AbstractOperation extends AbstractExpression 
{
	/**
	 * @var String
	 */
	protected $operator;
	
	/**
	 * @return string
	 */
	public function getOperator()
	{
		return $this->operator;
	}

	/**
	 * @param string $operator
	 */
	public function setOperator($operator)
	{
		$this->operator = $operator;
	}
}