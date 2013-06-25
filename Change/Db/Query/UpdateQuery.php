<?php
namespace Change\Db\Query;

/**
 * @api
 * @name \Change\Db\Query\UpdateQuery
 */
class UpdateQuery extends AbstractQuery
{
	/**
	 * @var Clauses\UpdateClause
	 */
	protected $updateClause;

	/**
	 * @var Clauses\SetClause
	 */
	protected $setClause;

	/**
	 * @var Clauses\WhereClause
	 */
	protected $whereClause;

	/**
	 * @api
	 * @return Clauses\UpdateClause|null
	 */
	public function getUpdateClause()
	{
		return $this->updateClause;
	}

	/**
	 * @api
	 * @return Clauses\SetClause|null
	 */
	public function getSetClause()
	{
		return $this->setClause;
	}

	/**
	 * @api
	 * @return Clauses\WhereClause|null
	 */
	public function getWhereClause()
	{
		return $this->whereClause;
	}

	/**
	 * @api
	 * @param Clauses\UpdateClause $updateClause
	 */
	public function setUpdateClause(Clauses\UpdateClause $updateClause)
	{
		$this->updateClause = $updateClause;
	}

	/**
	 * @api
	 * @param Clauses\SetClause $setClause
	 */
	public function setSetClause(Clauses\SetClause $setClause)
	{
		$this->setClause = $setClause;
	}

	/**
	 * @api
	 * @param Clauses\WhereClause $whereClause
	 */
	public function setWhereClause(Clauses\WhereClause $whereClause)
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
			throw new \RuntimeException('UpdateClause can not be null', 42021);
		}
		if ($this->setClause === null)
		{
			throw new \RuntimeException('SetClause can not be null', 42022);
		}
	}

	/**
	 * @api
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
		return $this->getDbProvider()->executeQuery($this);
	}
}
