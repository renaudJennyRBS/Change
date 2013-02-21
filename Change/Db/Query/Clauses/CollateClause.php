<?php
namespace Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\AbstractClause;

/**
 * @name \Change\Db\Query\Clauses\CollateClause
 */
class CollateClause extends AbstractClause
{
	/**
	 * @var \Change\Db\Query\Expressions\AbstractExpression
	 */
	protected $expression;
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function __construct(\Change\Db\Query\Expressions\AbstractExpression $expression = null)
	{
		$this->setName('COLLATE');
		if ($expression)
		{
			$this->setExpression($expression);
		}
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression|null
	 */
	public function getExpression()
	{
		return $this->expression;
	}

	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 */
	public function setExpression(\Change\Db\Query\Expressions\AbstractExpression $expression)
	{
		$this->expression = $expression;
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->getExpression() === null)
		{
			throw new \RuntimeException('Expression can not be null', 42023);
		}
		return 'COLLATE ' . $this->getExpression()->toSQL92String();
	}
}