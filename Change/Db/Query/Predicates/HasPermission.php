<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Predicates;

use Change\Db\Query;

/**
* @name \Change\Db\Query\Predicates\HasPermission
*/
class HasPermission implements InterfacePredicate
{
	/**
	 * @var Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList
	 */
	protected $accessor;

	/**
	 * @var Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList
	 */
	protected $role;

	/**
	 * @var Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList
	 */
	protected $resource;

	/**
	 * @var Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList
	 */
	protected $privilege;

	/**
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList $accessor
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList $role
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList $resource
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList $privilege
	 */
	function __construct($accessor = null, $role = null, $resource = null, $privilege = null)
	{
		$this->setAccessor($accessor);
		$this->setRole($role);
		$this->setResource($resource);
		$this->setPrivilege($privilege);
	}

	/**
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList $accessor
	 * @return $this
	 */
	public function setAccessor($accessor)
	{
		$this->accessor = $accessor;
		return $this;
	}

	/**
	 * @return Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList
	 */
	public function getAccessor()
	{
		return $this->accessor;
	}

	/**
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList $role
	 * @return $this
	 */
	public function setRole($role)
	{
		$this->role = $role;
		return $this;
	}

	/**
	 * @return Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList
	 */
	public function getRole()
	{
		return $this->role;
	}

	/**
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList $resource
	 * @return $this
	 */
	public function setResource($resource)
	{
		$this->resource = $resource;
		return $this;
	}

	/**
	 * @return Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList $privilege
	 */
	public function setPrivilege($privilege)
	{
		$this->privilege = $privilege;
	}

	/**
	 * @return Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList
	 */
	public function getPrivilege()
	{
		return $this->privilege;
	}

	/**
	 * @param string $columnName
	 * @param Query\Expressions\String|Query\Expressions\Numeric $genericValue
	 * @param Query\Expressions\AbstractExpression|Query\Expressions\ExpressionList|null $predicate
	 * @throws \RuntimeException
	 * @return BinaryPredicate|Disjunction|In|null
	 */
	protected function buildPropertyPredicate($columnName, $genericValue, $predicate)
	{
		$column = new Query\Expressions\Column(new Query\Expressions\Identifier(array($columnName)));

		if ($predicate === null)
		{
			return new Query\Predicates\BinaryPredicate($column, $genericValue, Query\Predicates\BinaryPredicate::EQUAL);
		}
		elseif ($predicate instanceof Query\Expressions\ExpressionList)
		{
			$list = clone($predicate);
			$list->add($genericValue);
			return new Query\Predicates\In($column, $list);
		}
		elseif ($predicate instanceof Query\Expressions\AbstractExpression)
		{
			$d1 = new Query\Predicates\BinaryPredicate($column, $predicate, Query\Predicates\BinaryPredicate::EQUAL);
			$d2 = new Query\Predicates\BinaryPredicate($column, $genericValue, Query\Predicates\BinaryPredicate::EQUAL);
			return new Query\Predicates\Disjunction($d1, $d2);
		}
		else
		{
			throw new \RuntimeException('Invalid ' . $column->getColumnName()->toSQL92String(). ' property value.', 999999);
		}
	}

	/**
	 * @return BinaryPredicate|Disjunction|In|null
	 */
	public function getAccessorPredicate()
	{
		return $this->buildPropertyPredicate('accessor_id', new Query\Expressions\Numeric(0), $this->accessor);
	}

	/**
	 * @return BinaryPredicate|Disjunction|In|null
	 */
	public function getRolePredicate()
	{
		return $this->buildPropertyPredicate('role', new Query\Expressions\String('*'), $this->role);
	}

	/**
	 * @return BinaryPredicate|Disjunction|In|null
	 */
	public function getResourcePredicate()
	{
		return $this->buildPropertyPredicate('resource_id', new Query\Expressions\Numeric(0), $this->resource);
	}

	/**
	 * @return BinaryPredicate|Disjunction|In|null
	 */
	public function getPrivilegePredicate()
	{
		return $this->buildPropertyPredicate('privilege', new Query\Expressions\String('*'), $this->privilege);
	}

	/**
	 * @return Exists
	 */
	public function getPredicate()
	{
		$sq = new Query\SelectQuery();
		$sq->setSelectClause(new Query\Clauses\SelectClause());
		$fromClause = new Query\Clauses\FromClause();
		$fromClause->setTableExpression(new Query\Expressions\Table('change_permission_rule'));
		$sq->setFromClause($fromClause);

		$and = new Query\Predicates\Conjunction($this->getAccessorPredicate(), $this->getRolePredicate(),
			$this->getResourcePredicate(), $this->getPrivilegePredicate());
		$where = new Query\Clauses\WhereClause($and);
		$sq->setWhereClause($where);
		return new Query\Predicates\Exists(new Query\Expressions\SubQuery($sq));
	}

	/**
	 * @api
	 * @return string
	 */
	public function toSQL92String()
	{
		return $this->getPredicate()->toSQL92String();
	}
}