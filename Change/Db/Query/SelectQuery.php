<?php
namespace Change\Db\Query;

/**
 * @api
 * @name \Change\Db\Query\SelectQuery
 */
class SelectQuery extends AbstractQuery
{
	/**
	 * @var Clauses\SelectClause
	 */
	protected $selectClause;
	
	/**
	 * @var Clauses\FromClause
	 */
	protected $fromClause;
	
	/**
	 * @var Clauses\WhereClause
	 */
	protected $whereClause;
	
	/**
	 * @var Clauses\HavingClause
	 */
	protected $havingClause;
	
	/**
	 * @var Clauses\OrderByClause
	 */
	protected $orderByClause;
	
	/**
	 * @var Clauses\GroupByClause
	 */
	protected $groupByClause;
	
	/**
	 * @var Clauses\CollateClause
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
	 * @api
	 * @return Clauses\SelectClause|null
	 */
	public function getSelectClause()
	{
		return $this->selectClause;
	}
	
	/**
	 * @api
	 * @param Clauses\SelectClause $selectClause
	 */
	public function setSelectClause(Clauses\SelectClause $selectClause)
	{
		$this->selectClause = $selectClause;
	}
	
	/**
	 * @api
	 * @return Clauses\FromClause
	 */
	public function getFromClause()
	{
		return $this->fromClause;
	}
	
	/**
	 * @api
	 * @param Clauses\FromClause $fromClause
	 */
	public function setFromClause(Clauses\FromClause $fromClause)
	{
		$this->fromClause = $fromClause;
	}
	
	/**
	 * @api
	 * @return Clauses\WhereClause
	 */
	public function getWhereClause()
	{
		return $this->whereClause;
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
	 * @return Clauses\GroupByClause
	 */
	public function getGroupByClause()
	{
		return $this->groupByClause;
	}
	
	/**
	 * @api
	 * @param Clauses\GroupByClause $groupByClause
	 */
	public function setGroupByClause(Clauses\GroupByClause $groupByClause)
	{
		$this->groupByClause = $groupByClause;
	}
	
	/**
	 * @api
	 * @return Clauses\HavingClause
	 */
	public function getHavingClause()
	{
		return $this->havingClause;
	}
	
	/**
	 * @api
	 * @param Clauses\HavingClause $havingClause
	 */
	public function setHavingClause(Clauses\HavingClause $havingClause)
	{
		$this->havingClause = $havingClause;
	}
	
	/**
	 * @api
	 * @return Clauses\OrderByClause
	 */
	public function getOrderByClause()
	{
		return $this->orderByClause;
	}
	
	/**
	 * @api
	 * @param Clauses\OrderByClause $orderByClause
	 */
	public function setOrderByClause(Clauses\OrderByClause $orderByClause)
	{
		$this->orderByClause = $orderByClause;
	}
	
	/**
	 * @api
	 * @return Clauses\CollateClause
	 */
	public function getCollateClause()
	{
		return $this->collateClause;
	}

	/**
	 * @api
	 * @param Clauses\CollateClause $collateClause
	 */
	public function setCollateClause(Clauses\CollateClause $collateClause)
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
			throw new \RuntimeException('SelectClause can not be null', 42011);
		}
	}
	
	/**
	 * @api
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
	 * @api
	 * @param integer|null $startIndex
	 * @return $this
	 */
	public function setStartIndex($startIndex)
	{
		$this->startIndex = $startIndex;
		return $this;
	}

	/**
	 * @api
	 * @return integer|null
	 */
	public function getStartIndex()
	{
		return $this->startIndex;
	}

	/**
	 * @api
	 * @param integer|null $maxResults
	 * @return $this
	 */
	public function setMaxResults($maxResults)
	{
		$this->maxResults = $maxResults;
		return $this;
	}

	/**
	 * @api
	 * @return integer|null
	 */
	public function getMaxResults()
	{
		return $this->maxResults;
	}

	/**
	 * @return ResultsConverter
	 */
	public function getRowsConverter()
	{
		$dbp = $this->getDbProvider();
		return new ResultsConverter(function($dbValue, $dbType) use ($dbp) {return $dbp->dbToPhp($dbValue, $dbType);});
	}

	/**
	 * @api
	 * @param \Closure|array $rowsConverter
	 * @return array rows
	 */
	public function getResults($rowsConverter = null)
	{
		$results = $this->getDbProvider()->getQueryResultsArray($this);
		if (is_array($results) && count($results))
		{
			return is_callable($rowsConverter) ? call_user_func($rowsConverter, $results) : $results;
		}
		return array();
	}
	
	/**
	 * @api
	 * @param \Closure|array $rowConverter
	 * @return array|mixed|null row
	 */
	public function getFirstResult($rowConverter = null)
	{
		$this->maxResults = 1;
		$rows = $this->getResults(null);
		if (count($rows))
		{
			return is_callable($rowConverter) ? call_user_func($rowConverter, $rows[0]) : $rows[0];
		}
		return null;
	}
}
