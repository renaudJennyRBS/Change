<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Tag\Db\Query;

use Change\Db\Query;
use Change\Db\Query\Expressions\Column;
use Change\Db\Query\Expressions\Identifier;

class HasTag extends Query\Expressions\AbstractExpression implements Query\Predicates\InterfacePredicate
{
	/**
	 * @var Query\Expressions\Column
	 */
	protected $documentIdColumn;

	/**
	 * @var Query\Expressions\AbstractExpression
	 */
	protected $tagId;

	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $tagId
	 */
	public function setTagId($tagId)
	{
		$this->tagId = $tagId;
	}

	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getTagId()
	{
		return $this->tagId;
	}

	/**
	 * @param \Change\Db\Query\Expressions\Column $documentIdColumn
	 */
	public function setDocumentIdColumn($documentIdColumn)
	{
		$this->documentIdColumn = $documentIdColumn;
	}

	/**
	 * @return \Change\Db\Query\Expressions\Column
	 */
	public function getDocumentIdColumn()
	{
		return $this->documentIdColumn;
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if (!($this->getDocumentIdColumn() instanceof Query\Expressions\AbstractExpression))
		{
			throw new \RuntimeException('Invalid documentId column Expression', 42030);
		}

		if (!($this->getTagId() instanceof Query\Expressions\AbstractExpression))
		{
			throw new \RuntimeException('Invalid TagId Expression', 42030);
		}
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		return $this->buildPredicate()->toSQL92String();
	}

	/**
	 * @return Query\Predicates\InterfacePredicate
	 */
	protected function buildPredicate()
	{
		$sq = new Query\SelectQuery();
		$sq->setSelectClause(new Query\Clauses\SelectClause());
		$fromClause = new Query\Clauses\FromClause();
		$fromClause->setTableExpression(new Query\Expressions\Table('rbs_tag_document'));
		$p = new Query\Expressions\Parentheses(new Column(new Identifier(array('tag_id'))));
		$joinExpr = new Query\Expressions\UnaryOperation($p, 'USING');
		$join = new  Query\Expressions\Join(new Query\Expressions\Table('rbs_tag_search'), Query\Expressions\Join::INNER_JOIN,
			$joinExpr);

		$fromClause->addJoin($join);
		$sq->setFromClause($fromClause);

		$docEq = new Query\Predicates\BinaryPredicate(
			new Column(new Identifier(array('doc_id'))), $this->getDocumentIdColumn(), Query\Predicates\BinaryPredicate::EQUAL);
		$tagEq = new Query\Predicates\BinaryPredicate(
			new Column(new Identifier(array('search_tag_id'))), $this->getTagId(), Query\Predicates\BinaryPredicate::EQUAL);
		$and = new Query\Predicates\Conjunction($docEq, $tagEq);
		$where = new Query\Clauses\WhereClause($and);
		$sq->setWhereClause($where);
		return new Query\Predicates\Exists(new Query\Expressions\SubQuery($sq));
	}

	/**
	 * @param \Change\Db\DbProvider $provider
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQLString($provider)
	{
		$this->checkCompile();
		return $provider->buildSQLFragment($this->buildPredicate());
	}

	/**
	 * @param array $predicateJSON
	 * @param \Change\Documents\Query\JSONDecoder $JSONDecoder
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @return \Change\Db\Query\Predicates\Exists
	 */
	public function populate($predicateJSON, $JSONDecoder, $predicateBuilder)
	{
		if (is_array($predicateJSON) && $JSONDecoder instanceof \Change\Documents\Query\JSONDecoder
			&& $predicateBuilder instanceof \Change\Documents\Query\PredicateBuilder)
		{
			$this->setDocumentIdColumn($predicateBuilder->columnProperty('id'));
			$this->setTagId($JSONDecoder->getDocumentQuery()->getValueAsParameter($predicateJSON['tag'],
				\Change\Documents\Property::TYPE_INTEGER));
			return $this->buildPredicate();
		}
	}
}