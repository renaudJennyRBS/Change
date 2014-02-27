<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\SubQuery
 */
class SubQuery extends AbstractExpression
{
	/**
	 * @var \Change\Db\Query\SelectQuery
	 */
	protected $subQuery;
	
	/**
	 * @param \Change\Db\Query\SelectQuery $subQuery
	 */
	public function __construct(\Change\Db\Query\SelectQuery $subQuery)
	{
		$this->subQuery = $subQuery;
	}
	
	/**
	 * @return \Change\Db\Query\SelectQuery
	 */
	public function getSubQuery()
	{
		return $this->subQuery;
	}
	
	/**
	 * @param \Change\Db\Query\SelectQuery $subQuery
	 */
	public function setSubQuery(\Change\Db\Query\SelectQuery $subQuery)
	{
		$this->subQuery = $subQuery;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return '(' . $this->getSubQuery()->toSQL92String() . ')';
	}
}