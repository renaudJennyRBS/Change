<?php
namespace Change\Db\Query;

/**
 * @api
 * @name \Change\Db\Query\InsertQuery
 */
class InsertQuery extends AbstractQuery
{
	/**
	 * @var Clauses\InsertClause
	 */
	protected $insertClause;

	/**
	 * @var Clauses\ValuesClause
	 */
	protected $valuesClause;

	/**
	 * @var SelectQuery
	 */
	protected $selectQuery;

	/**
	 * @api
	 * @return Clauses\InsertClause|null
	 */
	public function getInsertClause()
	{
		return $this->insertClause;
	}

	/**
	 * @api
	 * @return Clauses\ValuesClause|null
	 */
	public function getValuesClause()
	{
		return $this->valuesClause;
	}

	/**
	 * @api
	 * @return SelectQuery|null
	 */
	public function getSelectQuery()
	{
		return $this->selectQuery;
	}

	/**
	 * @api
	 * @param Clauses\InsertClause $insertClause
	 */
	public function setInsertClause(Clauses\InsertClause $insertClause)
	{
		$this->insertClause = $insertClause;
	}

	/**
	 * @api
	 * @param Clauses\ValuesClause $valuesClause
	 */
	public function setValuesClause(Clauses\ValuesClause $valuesClause)
	{
		$this->valuesClause = $valuesClause;
	}

	/**
	 * @api
	 * @param SelectQuery $selectQuery
	 */
	public function setSelectQuery(SelectQuery $selectQuery)
	{
		$this->selectQuery = $selectQuery;
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if ($this->insertClause === null)
		{
			throw new \RuntimeException('InsertClause can not be null', 42008);
		}
		if ($this->valuesClause === null && $this->selectQuery === null)
		{
			throw new \RuntimeException('ValuesClause or SelectQuery should be not null', 42009);
		}
		if ($this->valuesClause !== null && $this->selectQuery !== null)
		{
			throw new \RuntimeException('ValuesClause or SelectQuery should be null', 42010);
		}
	}

	/**
	 * @api
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		$parts = array($this->insertClause->toSQL92String());
		if ($this->valuesClause !== null)
		{
			$parts[] = $this->valuesClause->toSQL92String();
		}
		else
		{
			$parts[] = $this->selectQuery->toSQL92String();
		}

		return implode(' ', $parts);
	}

	/**
	 * @api
	 * @return integer
	 */
	public function execute()
	{
		return $this->getDbProvider()->executeQuery($this);
	}
}
