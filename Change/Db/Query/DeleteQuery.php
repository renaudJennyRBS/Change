<?php
namespace Change\Db\Query;

/**
 * @name \Change\Db\Query\DeleteQuery
 */
class DeleteQuery extends \Change\Db\Query\AbstractQuery
{
	/**
	 * @var \Change\Db\Query\Clauses\DeleteClause
	 */
	protected $deleteClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\FromClause
	 */
	protected $fromClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\WhereClause
	 */
	protected $whereClause;
	
	/**
	 * @return \Change\Db\Query\Clauses\DeleteClause|null
	 */
	public function getDeleteClause()
	{
		return $this->deleteClause;
	}

	/**
	 * @param \Change\Db\Query\Clauses\DeleteClause $deleteClause
	 */
	public function setDeleteClause(\Change\Db\Query\Clauses\DeleteClause $deleteClause)
	{
		$this->deleteClause = $deleteClause;
	}

	/**
	 * @return \Change\Db\Query\Clauses\FromClause|null
	 */
	public function getFromClause()
	{
		return $this->fromClause;
	}

	/**
	 * @param \Change\Db\Query\Clauses\FromClause $fromClause
	 */
	public function setFromClause(\Change\Db\Query\Clauses\FromClause $fromClause)
	{
		$this->fromClause = $fromClause;
	}

	/**
	 * @return \Change\Db\Query\Clauses\WhereClause|null
	 */
	public function getWhereClause()
	{
		return $this->whereClause;
	}

	/**
	 * @param \Change\Db\Query\Clauses\WhereClause $whereClause
	 */
	public function setWhereClause(\Change\Db\Query\Clauses\WhereClause $whereClause)
	{
		$this->whereClause = $whereClause;
	}
	
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if ($this->deleteClause === null)
		{
			throw new \RuntimeException('DeleteClause can not be null');
		}		
		if ($this->fromClause === null)
		{
			throw new \RuntimeException('FromClause can not be null');
		}
	}
		
	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
	
		$parts = array($this->deleteClause->toSQL92String(), $this->fromClause->toSQL92String());
		if ($this->whereClause !== null)
		{
			$parts[] = $this->whereClause->toSQL92String();
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
