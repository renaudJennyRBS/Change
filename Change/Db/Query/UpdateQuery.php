<?php
namespace Change\Db\Query;

/**
 * @name \Change\Db\Query\UpdateQuery
 */
class UpdateQuery extends \Change\Db\Query\AbstractQuery
{
	/**
	 * @var \Change\Db\Query\Clauses\UpdateClause
	 */
	protected $updateClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\SetClause
	 */
	protected $setClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\WhereClause
	 */
	protected $whereClause;
	
	/**
	 * @return \Change\Db\Query\Clauses\UpdateClause|null
	 */
	public function getUpdateClause()
	{
		return $this->updateClause;
	}

	/**
	 * @return \Change\Db\Query\Clauses\SetClause|null
	 */
	public function getSetClause()
	{
		return $this->setClause;
	}

	/**
	 * @return \Change\Db\Query\Clauses\WhereClause|null
	 */
	public function getWhereClause()
	{
		return $this->whereClause;
	}

	/**
	 * @param \Change\Db\Query\Clauses\UpdateClause $updateClause
	 */
	public function setUpdateClause(\Change\Db\Query\Clauses\UpdateClause $updateClause)
	{
		$this->updateClause = $updateClause;
	}

	/**
	 * @param \Change\Db\Query\Clauses\SetClause $setClause
	 */
	public function setSetClause(\Change\Db\Query\Clauses\SetClause $setClause)
	{
		$this->setClause = $setClause;
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
		if ($this->updateClause === null)
		{
			throw new \RuntimeException('UpdateClause can not be null');
		}		
		if ($this->setClause === null)
		{
			throw new \RuntimeException('SetClause can not be null');
		}
	}

	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		$parts = array($this->updateClause->toSQL92String(), $this->setClause->toSQL92String());
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
