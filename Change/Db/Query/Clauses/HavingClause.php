<?php
namespace Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\AbstractClause;

/**
 * @name \Change\Db\Query\Clauses\HavingClause
 */
class HavingClause extends AbstractClause
{
	/**
	 * @var Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected $predicate;
	
	/**
	 * @param \Change\Db\Query\Predicates\InterfacePredicate $predicate
	 */
	public function __construct(\Change\Db\Query\Predicates\InterfacePredicate $predicate = null)
	{
		$this->setName('HAVING');
		if ($predicate) 
		{
			$this->setPredicate($predicate);
		}
	}
	
	/**
	 * @return \Change\Db\Query\Predicates\InterfacePredicate|null
	 */
	public function getPredicate()
	{
		return $this->predicate;
	}
	
	/**
	 * @param \Change\Db\Query\Predicates\InterfacePredicate $predicate
	 */
	public function setPredicate(\Change\Db\Query\Predicates\InterfacePredicate $predicate)
	{
		$this->predicate = $predicate;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->getPredicate())
		{
			return 'HAVING ' . $this->getPredicate()->toSQL92String();
		}
		return '';
	}
}