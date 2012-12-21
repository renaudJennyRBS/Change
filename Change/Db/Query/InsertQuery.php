<?php
namespace Change\Db\Query;

/**
 * @name \Change\Db\Query\InsertQuery
 */
class InsertQuery extends \Change\Db\Query\AbstractQuery
{
	/**
	 * @var \Change\Db\Query\Clauses\InsertClause
	 */
	protected $insertClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\ValuesClause
	 */
	protected $valuesClause;
	
	/**
	 * @var \Change\Db\Query\SelectQuery
	 */
	protected $selectQuery;
	
	
	/**
	 * @return \Change\Db\Query\Clauses\InsertClause|null
	 */
	public function getInsertClause()
	{
		return $this->insertClause;
	}

	/**
	 * @return \Change\Db\Query\Clauses\ValuesClause|null
	 */
	public function getValuesClause()
	{
		return $this->valuesClause;
	}

	/**
	 * @return \Change\Db\Query\SelectQuery|null
	 */
	public function getSelectQuery()
	{
		return $this->selectQuery;
	}

	/**
	 * @param \Change\Db\Query\Clauses\InsertClause $insertClause
	 */
	public function setInsertClause(\Change\Db\Query\Clauses\InsertClause $insertClause)
	{
		$this->insertClause = $insertClause;
	}

	/**
	 * @param \Change\Db\Query\Clauses\ValuesClause $valuesClause
	 */
	public function setValuesClause(\Change\Db\Query\Clauses\ValuesClause $valuesClause)
	{
		$this->valuesClause = $valuesClause;
	}

	/**
	 * @param \Change\Db\Query\SelectQuery $selectQuery
	 */
	public function setSelectQuery(\Change\Db\Query\SelectQuery $selectQuery)
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
			throw new \RuntimeException('InsertClause can not be null');
		}		
		if ($this->valuesClause === null && $this->selectQuery === null)
		{
			throw new \RuntimeException('ValuesClause or SelectQuery should be not null');
		}
		if ($this->valuesClause !== null && $this->selectQuery !== null)
		{
			throw new \RuntimeException('ValuesClause or SelectQuery should be null');
		}
	}

	/**
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
		return $this->dbProvider->executeQuery($this);	
	}
}
