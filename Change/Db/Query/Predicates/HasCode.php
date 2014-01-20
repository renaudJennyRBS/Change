<?php
namespace Change\Db\Query\Predicates;

use Change\Db\Query;
use Change\Db\Query\Expressions\Column;
use Change\Db\Query\Expressions\Identifier;

/**
 * @name \Change\Db\Query\Predicates\HasCode
 */
class HasCode extends Query\Expressions\AbstractExpression implements Query\Predicates\InterfacePredicate
{
	/**
	 * @var Query\Expressions\Column
	 */
	protected $documentIdColumn;

	/**
	 * @var Query\Expressions\AbstractExpression
	 */
	protected $code;

	/**
	 * @var Query\Expressions\AbstractExpression
	 */
	protected $contextId;

	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $tagId
	 */
	public function setCode($tagId)
	{
		$this->code = $tagId;
	}

	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $contextId
	 * @return $this
	 */
	public function setContextId($contextId)
	{
		$this->contextId = $contextId;
		return $this;
	}

	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression
	 */
	public function getContextId()
	{
		return $this->contextId;
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

		if (!($this->getCode() instanceof Query\Expressions\AbstractExpression))
		{
			throw new \RuntimeException('Invalid Code Expression', 42030);
		}

		if (!($this->getContextId() instanceof Query\Expressions\AbstractExpression))
		{
			throw new \RuntimeException('Invalid Context id Expression', 42030);
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
		$fromClause->setTableExpression(new Query\Expressions\Table('change_document_code'));
		$sq->setFromClause($fromClause);

		$docEq = new Query\Predicates\BinaryPredicate(
			new Column(new Identifier(array('change_document_code', 'document_id'))), $this->getDocumentIdColumn(), Query\Predicates\BinaryPredicate::EQUAL);

		$codeEq = new Query\Predicates\BinaryPredicate(
			new Column(new Identifier(array('change_document_code', 'code'))), $this->getCode(), Query\Predicates\BinaryPredicate::EQUAL);

		$contextEq = new Query\Predicates\BinaryPredicate(
			new Column(new Identifier(array('change_document_code', 'context_id'))), $this->getContextId(), Query\Predicates\BinaryPredicate::EQUAL);

		$and = new Query\Predicates\Conjunction($docEq, $codeEq, $contextEq);
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
	 * @param \Change\Documents\Query\PredicateBuilder $predicateBuilder
	 * @param  \Change\Documents\DocumentCodeManager $documentCodeManager
	 * @return \Change\Db\Query\Predicates\Exists| null
	 */
	public function populate(array $predicateJSON, \Change\Documents\Query\PredicateBuilder $predicateBuilder,
		\Change\Documents\DocumentCodeManager $documentCodeManager)
	{
		if (!isset($predicateJSON['code']) || !is_string($predicateJSON['code']))
		{
			return null;
		}
		$code = $predicateJSON['code'];
		$context = isset($predicateJSON['context']) ? $predicateJSON['context'] : 0;
		$contextId = $documentCodeManager->resolveContextId($context);
		if ($contextId === false)
		{
			return null;
		}
		$this->setDocumentIdColumn($predicateBuilder->columnProperty('id'));
		$this->setCode($predicateBuilder->getBuilder()->getValueAsParameter($code, \Change\Documents\Property::TYPE_STRING));
		$this->setContextId($predicateBuilder->getBuilder()->getValueAsParameter($contextId, \Change\Documents\Property::TYPE_INTEGER));
		return $this->buildPredicate();
	}
}