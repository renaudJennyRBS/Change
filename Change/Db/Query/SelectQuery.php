<?php
namespace Change\Db\Query;

/**
 * @name \Change\Db\Query\SelectQuery
 */
class SelectQuery extends \Change\Db\Query\AbstractQuery
{
	/**
	 * @var \Change\Db\Query\Clauses\SelectClause
	 */
	protected $selectClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\FromClause
	 */
	protected $fromClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\WhereClause
	 */
	protected $whereClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\HavingClause
	 */
	protected $havingClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\OrderByClause
	 */
	protected $orderByClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\GroupByClause
	 */
	protected $groupByClause;
	
	/**
	 * @var \Change\Db\Query\Clauses\CollateClause
	 */
	protected $collateClause;
	
	/**
	 * @var integer
	 */
	protected $startIndex;
	
	/**
	 * @var integer
	 */
	protected $maxResults;
	
	/**
	 * @return \Change\Db\Query\Clauses\SelectClause|null
	 */
	public function getSelectClause()
	{
		return $this->selectClause;
	}
	
	/**
	 * @param \Change\Db\Query\Clauses\SelectClause $selectClause
	 */
	public function setSelectClause(\Change\Db\Query\Clauses\SelectClause $selectClause)
	{
		$this->selectClause = $selectClause;
	}
	
	/**
	 * @return \Change\Db\Query\Clauses\FromClause
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
	 * @return \Change\Db\Query\Clauses\WhereClause
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
	 *
	 * @return \Change\Db\Query\Clauses\GroupByClause
	 */
	public function getGroupByClause()
	{
		return $this->groupByClause;
	}
	
	/**
	 * @param \Change\Db\Query\Clauses\GroupByClause $groupByClause
	 */
	public function setGroupByClause(\Change\Db\Query\Clauses\GroupByClause $groupByClause)
	{
		$this->groupByClause = $groupByClause;
	}
	
	/**
	 * @return \Change\Db\Query\Clauses\HavingClause
	 */
	public function getHavingClause()
	{
		return $this->havingClause;
	}
	
	/**
	 * @param \Change\Db\Query\Clauses\HavingClause $havingClause
	 */
	public function setHavingClause(\Change\Db\Query\Clauses\HavingClause $havingClause)
	{
		$this->havingClause = $havingClause;
	}
	
	/**
	 * @return \Change\Db\Query\Clauses\OrderByClause
	 */
	public function getOrderByClause()
	{
		return $this->orderByClause;
	}
	
	/**
	 * @param \Change\Db\Query\Clauses\OrderByClause $orderByClause
	 */
	public function setOrderByClause(\Change\Db\Query\Clauses\OrderByClause $orderByClause)
	{
		$this->orderByClause = $orderByClause;
	}
	
	/**
	 * @return \Change\Db\Query\Clauses\CollateClause
	 */
	public function getCollateClause()
	{
		return $this->collateClause;
	}

	/**
	 * @param \Change\Db\Query\Clauses\CollateClause $collateClause
	 */
	public function setCollateClause(\Change\Db\Query\Clauses\CollateClause $collateClause)
	{
		$this->collateClause = $collateClause;
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if ($this->selectClause === null)
		{
			throw new \RuntimeException('SelectClause can not be null');
		}
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$this->checkCompile();
		
		$parts = array($this->selectClause->toSQL92String());
		
		$fromClause = $this->getFromClause();
		if ($fromClause)
		{
			$parts[] = $fromClause->toSQL92String();
		}
		$whereClause = $this->getWhereClause();
		if ($whereClause)
		{
			$parts[] = $whereClause->toSQL92String();
		}
		
		$groupByClause = $this->getGroupByClause();
		if ($groupByClause)
		{
			$parts[] = $groupByClause->toSQL92String();
		}
		
		$havingClause = $this->getHavingClause();
		if ($havingClause)
		{
			$parts[] = $havingClause->toSQL92String();
		}
		
		$orderByClause = $this->getOrderByClause();
		if ($orderByClause)
		{
			$parts[] = $orderByClause->toSQL92String();
		}
		
		$collateClause = $this->getCollateClause();
		if ($collateClause)
		{
			$parts[] = $collateClause->toSQL92String();
		}
				
		return implode(' ', $parts);
	}
	
	/**
	 * @return integer|null
	 */
	public function getStartIndex()
	{
		return $this->startIndex;
	}
	
	/**
	 * @return integer|null
	 */
	public function getMaxResults()
	{
		return $this->maxResults;
	}
	
	/**
	 * @param integer|null $startIndex
	 */
	public function setStartIndex($startIndex)
	{
		$this->startIndex = $startIndex;
	}
	
	/**
	 * @api
	 * @param integer|null $maxResults
	 */
	public function setMaxResults($maxResults)
	{
		$this->maxResults = $maxResults;
	}
	
	/**
	 * @api
	 * @param \Closure|array $rowsConverter
	 * @return array rows
	 */
	public function getResults($rowsConverter = null)
	{
		$results = $this->dbProvider->getQueryResultsArray($this);	
		return ($rowsConverter === null) ? $results : call_user_func($rowsConverter, $results);
	}
	
	/**
	 * @api
	 * @param \Closure|array $rowConverter
	 * @return array row
	 */
	public function getFirstResult($rowConverter = null)
	{
		$this->maxResults = 1;
		$rows = $this->getResults(null);
		if (count($rows))
		{
			return ($rowConverter === null) ? $rows[0] :  call_user_func($rowConverter, $rows[0]);
		}
		return null;
	}
}
