<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Clauses;

/**
 * @name \Change\Db\Query\Clauses\WhereClause
 */
class WhereClause extends AbstractClause
{
	/**
	 * @var \Change\Db\Query\Predicates\InterfacePredicate
	 */
	protected $predicate;
	
	/**
	 * @param \Change\Db\Query\Predicates\InterfacePredicate $predicate
	 */
	public function __construct(\Change\Db\Query\Predicates\InterfacePredicate $predicate = null)
	{
		$this->setName('WHERE');
		if ($predicate) 
		{
			$this->setPredicate($predicate);
		}
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
		if ($this->predicate)
		{
			return 'WHERE ' . $this->getPredicate()->toSQL92String();
		}
		return '';
	}
}