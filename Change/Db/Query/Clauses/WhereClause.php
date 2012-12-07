<?php
namespace Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\AbstractClause;

/**
 * @name \Change\Db\Query\Clauses\WhereClause
 */
class WhereClause extends AbstractClause
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
		if ($predicate) $this->setPredicate($predicate);
	}
	
	/**
	 * @return \Change\Db\Query\Predicates\InterfacePredicate
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
		return 'WHERE ' . $this->getPredicate()->toSQL92String();
	}
}