<?php
namespace Change\Db\Query\Predicates;

/**
 * @name \Change\Db\Query\Predicates\Exists
 * @api
 */
class Exists extends UnaryPredicate
{
	
	/**
	 * @var boolean
	 */
	protected $not = false;

	/**
	 * @param \Change\Db\Query\Expressions\SubQuery $subQuery
	 * @param bool $not
	 */
	public function __construct(\Change\Db\Query\Expressions\SubQuery $subQuery = null, $not = false)
	{
		parent::__construct($subQuery, 'EXISTS');
		$this->setNot($not);
	}
	
	/**
	 * @return boolean
	 */
	public function getNot()
	{
		return $this->not;
	}

	/**
	 * @param boolean $not
	 */
	public function setNot($not)
	{
		$this->not = ($not == true);
		$this->setOperator(($this->not) ? 'NOT EXISTS' : 'EXISTS');
	}
		
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		$subQuery = $this->getExpression();
		if (!($subQuery instanceof \Change\Db\Query\Expressions\SubQuery))
		{
			throw new \RuntimeException('Expression must be a SubQuery', 999999);
		}
	}

	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		return $this->getOperator() . $this->getExpression()->toSQL92String();
	}
}