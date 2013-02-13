<?php
namespace Change\Db\Mysql;

/**
 * @name \Change\Db\Mysql\DbProvider
 * @api
 */
class DbProvider extends \Change\Db\DbProvider
{
	/**
	 * @var \Change\Db\Mysql\SchemaManager
	 */
	protected $schemaManager = null;
	
	/**
	 * @var \PDO instance provided by PDODatabase
	 */
	private $m_driver = null;
	
	/**
	 * @var boolean
	 */
	protected $inTransaction = false;
	
	/**
	 * @return string
	 */
	public function getType()
	{
		return 'mysql';
	}
	
	/**
	 * @param \PDO|null $driver
	 */
	public function setDriver($driver)
	{
		$this->m_driver = $driver;
		if ($driver === null)
		{
			$duration = microtime(true) - $this->timers['init'];
			$this->timers['duration'] = $duration;
		}
	}
	
	/**
	 * @return \PDO
	 */
	public function getDriver()
	{
		if ($this->m_driver === null)
		{
			$this->m_driver = $this->getConnection($this->connectionInfos);
			register_shutdown_function(array($this, "closeConnection"));
		}
		return $this->m_driver;
	}
	
	/**
	 * @return string
	 */
	protected function errorCode()
	{
		return $this->getDriver()->errorCode();
	}
	
	/**
	 * @return array("sqlstate" => ..., "errorcode" => ..., "errormessage" => ...)
	 */
	protected function getErrorParameters()
	{
		$errorInfo = $this->getDriver()->errorInfo();
		return array("sqlstate" => $errorInfo[0], "errorcode" => $errorInfo[1], "errormessage" => $errorInfo[2]);
	}
	
	/**
	 * @return string
	 */
	protected function errorInfo()
	{
		return print_r($this->getDriver()->errorInfo(), true);
	}

	/**
	 * @param array $connectionInfos
	 * @throws \RuntimeException
	 * @return \PDO
	 */
	public function getConnection($connectionInfos)
	{
		$protocol = 'mysql';
		$dsnOptions = array();
		
		$database = isset($connectionInfos['database']) ? $connectionInfos['database'] : null;
		$password = isset($connectionInfos['password']) ? $connectionInfos['password'] : null;
		$username = isset($connectionInfos['user']) ? $connectionInfos['user'] : null;
		
		if ($database !== null)
		{
			$dsnOptions[] = 'dbname=' . $database;
		}
		else
		{
			throw new \RuntimeException('database not defined!');
		}
		$unix_socket = isset($connectionInfos['unix_socket']) ? $connectionInfos['unix_socket'] : null;
		if ($unix_socket != null)
		{
			$dsnOptions[] = 'unix_socket=' . $unix_socket;
		}
		else
		{
			$host = isset($connectionInfos['host']) ? $connectionInfos['host'] : 'localhost';
			$dsnOptions[] = 'host=' . $host;
			$port = isset($connectionInfos['port']) ? $connectionInfos['port'] : 3306;
			$dsnOptions[] = 'port=' . $port;
		}
		
		$dsn = $protocol . ':' . implode(';', $dsnOptions);
		
		$options = array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
		$pdo = new \PDO($dsn, $username, $password, $options);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		
		return $pdo;
	}
	
	/**
	 * @return void
	 */
	public function closeConnection()
	{
		$this->setDriver(null);
	}
	
	/**
	 * @return \Change\Db\Mysql\SchemaManager
	 */
	public function getSchemaManager()
	{
		if ($this->schemaManager === null)
		{
			$this->schemaManager = new SchemaManager($this, $this->logging);
		}
		return $this->schemaManager;
	}
	
	/**
	 * @return boolean
	 */
	public function inTransaction()
	{
		return $this->inTransaction;
	}
	
	/**
	 */
	public function beginTransaction()
	{
		if ($this->inTransaction())
		{
			$this->logging->warn(get_class($this) . " while already in transaction");
		}
		else
		{
			$this->timers['bt'] = microtime(true);
			$this->inTransaction = true;
			$this->getDriver()->beginTransaction();
		}
	}
	
	/**
	 */
	public function commit()
	{
		if (!$this->inTransaction())
		{
			$this->logging->warn(__METHOD__ . " called while not in transaction");
		}
		else
		{
			$this->getDriver()->commit();
			$duration = round(microtime(true) - $this->timers['bt'], 4);
			if ($duration > $this->timers['longTransaction'])
			{
				$this->logging->warn('Long Transaction detected ' . number_format($duration, 3) . 's > ' . $this->timers['longTransaction']);
			}
			$this->inTransaction = false;
		}
	}
	
	/**
	 */
	public function rollBack()
	{
		if (!$this->inTransaction())
		{
			$this->logging->warn(__METHOD__ . " called while not in transaction");
		}
		else
		{
			$this->inTransaction = false;
			$this->getDriver()->rollBack();
		}
	}
	
	/**
	 * @param string $tableName
	 * @return integer
	 */
	public function getLastInsertId($tableName)
	{
		return intval($this->getDriver()->lastInsertId($tableName));
	}

	/**
	 * @api
	 * @param \Change\Db\Query\InterfaceSQLFragment $fragment
	 * @throws \RuntimeException
	 * @return string
	 */
	public function buildSQLFragment(\Change\Db\Query\InterfaceSQLFragment $fragment)
	{
		if ($fragment instanceof \Change\Db\Query\Expressions\Table)
		{
			$identifierParts = array();
			$dbName = $fragment->getDatabase();
			$tableName = $fragment->getName();
			if (!empty($dbName))
			{
				$identifierParts[] = '`' . $dbName . '`';
			}
			$identifierParts[] = '`' . $tableName . '`';
			return implode('.', $identifierParts);
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\Column)
		{
			$columnName = $this->buildSQLFragment($fragment->getColumnName());
			$tableOrIdentifier = $fragment->getTableOrIdentifier();
			$table = ($tableOrIdentifier) ? $this->buildSQLFragment($tableOrIdentifier) : null;
			return empty($table) ? $columnName : $table . '.' . $columnName;
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\Parentheses)
		{
			return '(' . $this->buildSQLFragment($fragment->getExpression()) . ')';
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\Identifier)
		{
			return implode('.', array_map(function ($part)
			{
				return '`' . $part . '`';
			}, $fragment->getParts()));
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\Concat)
		{
			return 'CONCAT(' . implode(', ', $this->buildSQLFragmentArray($fragment->getList())) . ')';
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\ExpressionList)
		{
			return implode(', ', $this->buildSQLFragmentArray($fragment->getList()));
		}
		elseif ($fragment instanceof \Change\Db\Query\Predicates\Conjunction)
		{
			return '(' . implode(' AND ', $this->buildSQLFragmentArray($fragment->getArguments())) . ')';
		}
		elseif ($fragment instanceof \Change\Db\Query\Predicates\Disjunction)
		{
			return '(' . implode(' OR ', $this->buildSQLFragmentArray($fragment->getArguments())) . ')';
		}
		elseif ($fragment instanceof \Change\Db\Query\Predicates\Like)
		{
			$fragment->checkCompile();
			$rhe = $fragment->getCompletedRightHandExpression();
			return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' ' . $this->buildSQLFragment($rhe);
		}
		elseif ($fragment instanceof \Change\Db\Query\Predicates\In)
		{
			$fragment->checkCompile();
			$rhe = $fragment->getCompletedRightHandExpression();
			return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' ' . $this->buildSQLFragment($rhe);
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\BinaryOperation)
		{
			return $this->buildSQLFragment($fragment->getLeftHandExpression()) . ' ' . $fragment->getOperator() . ' ' . $this->buildSQLFragment($fragment->getRightHandExpression());
		}
		elseif ($fragment instanceof \Change\Db\Query\Predicates\UnaryPredicate)
		{
			return $this->buildSQLFragment($fragment->getExpression()) . ' ' . $fragment->getOperator();
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\OrderingSpecification)
		{
			return $this->buildSQLFragment($fragment->getExpression()) . ' ' . $fragment->getOperator();
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\UnaryOperation)
		{
			return $fragment->getOperator() . ' ' . $this->buildSQLFragment($fragment->getExpression());
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\Join)
		{
			$joinedTable = $fragment->getTableExpression();
			if (!$joinedTable)
			{
				throw new \RuntimeException('A joined table is required');
			}
			$parts = array();
			if ($fragment->isNatural())
			{
				$parts[] = 'NATURAL';
			}
			if ($fragment->isQualified())
			{
				switch ($fragment->getType())
				{
					case \Change\Db\Query\Expressions\Join::LEFT_OUTER_JOIN :
						$parts[] = 'LEFT OUTER JOIN';
						break;
					case \Change\Db\Query\Expressions\Join::RIGHT_OUTER_JOIN :
						$parts[] = 'RIGHT OUTER JOIN';
						break;
					case \Change\Db\Query\Expressions\Join::FULL_OUTER_JOIN :
						$parts[] = 'FULL OUTER JOIN';
						break;
					case \Change\Db\Query\Expressions\Join::INNER_JOIN :
					default :
						$parts[] = 'INNER JOIN';
						break;
				}
			}
			else
			{
				$parts[] = 'CROSS JOIN';
			}
			$parts[] = $this->buildSQLFragment($joinedTable);
			if (!$fragment->isNatural())
			{
				$joinSpecification = $fragment->getSpecification();
				$parts[] = $this->buildSQLFragment($joinSpecification);
			}
			return implode(' ', $parts);
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\Value)
		{
			$v = $fragment->getValue();
			if ($v === null)
			{
				return 'NULL';
			}
			switch ($fragment->getScalarType())
			{
				case \Change\Db\ScalarType::BOOLEAN :
					return ($v) ? '1' : '0';
				case \Change\Db\ScalarType::INTEGER :
					return strval(intval($v));
				case \Change\Db\ScalarType::DECIMAL :
					return strval(floatval($v));
				case \Change\Db\ScalarType::DATETIME :
					if ($v instanceof \DateTime)
					{
						$v->setTimezone(new \DateTimeZone('UTC'));
						$v = $v->format('Y-m-d H:i:s');
					}
			}
			return $this->getDriver()->quote($v);
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\Parameter)
		{
			return ':' . $fragment->getName();
		}
		elseif ($fragment instanceof \Change\Db\Query\Expressions\SubQuery)
		{
			return '(' . $this->buildQuery($fragment->getSubQuery()) . ')';
		}
		elseif ($fragment instanceof \Change\Db\Query\AbstractQuery)
		{
			return $this->buildQuery($fragment);
		}
		elseif ($fragment instanceof \Change\Db\Query\Clauses\AbstractClause)
		{
			return $this->buildAbstractClause($fragment);
		}
		$this->logging->info(__METHOD__ . '(' . get_class($fragment) . ') not implemted');
		return parent::buildSQLFragment($fragment);
	}
	
	/**
	 * @param \Change\Db\Query\InterfaceSQLFragment[] $fragments
	 * @return string[]
	 */
	protected function buildSQLFragmentArray($fragments)
	{
		$strings = array();
		foreach ($fragments as $fragment)
		{
			$strings[] = $this->buildSQLFragment($fragment);
		}
		return $strings;
	}
	
	/**
	 * @param \Change\Db\Query\AbstractQuery $query
	 * @return string
	 */
	protected function buildQuery(\Change\Db\Query\AbstractQuery $query)
	{
		if ($query instanceof \Change\Db\Query\SelectQuery)
		{
			$query->checkCompile();
			$parts = array($this->buildAbstractClause($query->getSelectClause()));
			$fromClause = $query->getFromClause();
			if ($fromClause)
			{
				$parts[] = $this->buildAbstractClause($fromClause);
			}
			$whereClause = $query->getWhereClause();
			if ($whereClause)
			{
				$parts[] = $this->buildAbstractClause($whereClause);
			}
			
			$groupByClause = $query->getGroupByClause();
			if ($groupByClause)
			{
				$parts[] = $this->buildAbstractClause($groupByClause);
			}
			
			$havingClause = $query->getHavingClause();
			if ($havingClause)
			{
				$parts[] = $this->buildAbstractClause($havingClause);
			}
			
			$orderByClause = $query->getOrderByClause();
			if ($orderByClause)
			{
				$parts[] = $this->buildAbstractClause($orderByClause);
			}
			
			if ($query->getMaxResults())
			{
				
				$parts[] = 'LIMIT';
				if ($query->getStartIndex())
				{
					$parts[] = strval(max(0, $query->getStartIndex())) . ',';
				}
				$parts[] = strval(max(1, $query->getMaxResults()));
			}
			
			return implode(' ', $parts);
		}
		elseif ($query instanceof \Change\Db\Query\InsertQuery)
		{
			$query->checkCompile();
			$parts = array($this->buildAbstractClause($query->getInsertClause()));
			if ($query->getValuesClause() !== null)
			{
				$parts[] = $this->buildAbstractClause($query->getValuesClause());
			}
			elseif ($query->getSelectQuery() !== null)
			{
				$parts[] = $this->buildQuery($query->getSelectQuery());
			}
			return implode(' ', $parts);
		}
		elseif ($query instanceof \Change\Db\Query\UpdateQuery)
		{
			$query->checkCompile();
			$parts = array($this->buildAbstractClause($query->getUpdateClause()), 
				$this->buildAbstractClause($query->getSetClause()));
			if ($query->getWhereClause() !== null)
			{
				$parts[] = $this->buildAbstractClause($query->getWhereClause());
			}
			return implode(' ', $parts);
		}
		elseif ($query instanceof \Change\Db\Query\DeleteQuery)
		{
			$query->checkCompile();
			$parts = array($this->buildAbstractClause($query->getDeleteClause()), 
				$this->buildAbstractClause($query->getFromClause()));
			if ($query->getWhereClause() !== null)
			{
				$parts[] = $this->buildAbstractClause($query->getWhereClause());
			}
			return implode(' ', $parts);
		}
		
		$this->logging->info(__METHOD__ . '(' . get_class($query) . ') not implemted');
		return parent::buildSQLFragment($query);
	}
	
	/**
	 * 
	 * @param \Change\Db\Query\Clauses\AbstractClause $clause
	 * @return string
	 */
	protected function buildAbstractClause(\Change\Db\Query\Clauses\AbstractClause $clause)
	{
		if ($clause instanceof \Change\Db\Query\Clauses\SelectClause)
		{
			$parts = array($clause->getName());
			if ($clause->getQuantifier() === \Change\Db\Query\Clauses\SelectClause::QUANTIFIER_DISTINCT)
			{
				$parts[] = \Change\Db\Query\Clauses\SelectClause::QUANTIFIER_DISTINCT;
			}
			$selectList = $clause->getSelectList();
			if ($selectList === null)
			{
				$selectList = new \Change\Db\Query\Expressions\AllColumns();
			}
			
			$parts[] = $this->buildSQLFragment($selectList);
			return implode(' ', $parts);
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\FromClause)
		{
			$clause->checkCompile();
			$parts = array($clause->getName(), $this->buildSQLFragment($clause->getTableExpression()));
			$parts[] = implode(' ', $this->buildSQLFragmentArray($clause->getJoins()));
			return implode(' ', $parts);
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\WhereClause)
		{
			$parts = array($clause->getName(), $this->buildSQLFragment($clause->getPredicate()));
			return implode(' ', $parts);
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\OrderByClause)
		{
			$clause->checkCompile();
			$parts = array($clause->getName(), $this->buildSQLFragment($clause->getExpressionList()));
			return implode(' ', $parts);
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\GroupByClause)
		{
			$clause->checkCompile();
			$parts = array($clause->getName(), $this->buildSQLFragment($clause->getExpressionList()));
			return implode(' ', $parts);
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\HavingClause)
		{
			return 'HAVING ' . $this->buildSQLFragment($clause->getPredicate());
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\InsertClause)
		{
			$clause->checkCompile();
			$insert = 'INSERT INTO ' . $this->buildSQLFragment($clause->getTable());
			$columns = $clause->getColumns();
			if (count($columns))
			{
				$compiler = $this;
				$insert .= ' (' . implode(', ', array_map(function ($column) use($compiler)
				{
					return $compiler->buildSQLFragment($column);
				}, $columns)) . ')';
			}
			return $insert;
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\ValuesClause)
		{
			$clause->checkCompile();
			return 'VALUES (' . $this->buildSQLFragment($clause->getValuesList()) . ')';
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\UpdateClause)
		{
			$clause->checkCompile();
			return 'UPDATE ' . $this->buildSQLFragment($clause->getTable());
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\SetClause)
		{
			$clause->checkCompile();
			return 'SET ' . $this->buildSQLFragment($clause->getSetList());
		}
		elseif ($clause instanceof \Change\Db\Query\Clauses\DeleteClause)
		{
			return 'DELETE';
		}
		
		$this->logging->info(__METHOD__ . '(' . get_class($clause) . ') not implemented');
		return parent::buildSQLFragment($clause);
	}
	
	/**
	 * @param \Change\Db\Query\SelectQuery $selectQuery
	 * @return array
	 */
	public function getQueryResultsArray(\Change\Db\Query\SelectQuery $selectQuery)
	{
		if ($selectQuery->getCachedSql() === null)
		{
			$selectQuery->setCachedSql($this->buildQuery($selectQuery));
			$this->logging->info(__METHOD__ . ': ' . $selectQuery->getCachedSql());
		}
		
		$statement = $this->prepareStatement($selectQuery->getCachedSql());
		foreach ($selectQuery->getParameters() as $parameter)
		{
			/* @var $parameter \Change\Db\Query\Expressions\Parameter */
			$value = $this->phpToDB($selectQuery->getParameterValue($parameter->getName()), $parameter->getType());
			$statement->bindValue($this->buildSQLFragment($parameter), $value);
		}
		$statement->execute();
		return $statement->fetchAll(\PDO::FETCH_ASSOC);
	}
	
	/**
	 * @param \Change\Db\Query\AbstractQuery $query
	 * @return integer
	 */
	public function executeQuery(\Change\Db\Query\AbstractQuery $query)
	{
		if ($query->getCachedSql() === null)
		{
			$query->setCachedSql($this->buildQuery($query));
			$this->logging->info(__METHOD__ . ': ' . $query->getCachedSql());
		}
		
		$statement = $this->prepareStatement($query->getCachedSql());
		foreach ($query->getParameters() as $parameter)
		{
			/* @var $parameter \Change\Db\Query\Expressions\Parameter */
			$value = $this->phpToDB($query->getParameterValue($parameter->getName()), $parameter->getType());
			$this->logging->info($parameter->getName() . ' = ' . var_export($value, true));
			$statement->bindValue($this->buildSQLFragment($parameter), $value);
		}
		$statement->execute();
		return $statement->rowCount();
	}
	
	/**
	 * @param string $sql
	 * @return \PDOStatement
	 */
	private function prepareStatement($sql)
	{
		return $this->getDriver()->prepare($sql);
	}
	
	/**
	 * @param \PDOStatement $stmt
	 */
	private function executeStatement($stmt)
	{
		$stmt->execute();
	}
	
	/**
	 * @param mixed $value
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public function phpToDB($value, $scalarType)
	{
		switch ($scalarType)
		{
			case \Change\Db\ScalarType::BOOLEAN :
				return ($value) ? 1 : 0;
			case \Change\Db\ScalarType::INTEGER :
				if ($value !== null)
				{
					return intval($value);
				}
				break;
			case \Change\Db\ScalarType::DECIMAL :
				if ($value !== null)
				{
					return floatval($value);
				}
				break;
			case \Change\Db\ScalarType::DATETIME :
				if ($value instanceof \DateTime)
				{
					$value->setTimezone(new \DateTimeZone('UTC'));
					return $value->format('Y-m-d H:i:s');
				}
				break;
		}
		return $value;
	}
	
	/**
	 * @param mixed $value
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public function dbToPhp($value, $scalarType)
	{
		switch ($scalarType)
		{
			case \Change\Db\ScalarType::BOOLEAN :
				return ($value == '1');
			case \Change\Db\ScalarType::INTEGER :
				if ($value !== null)
				{
					return intval($value);
				}
				break;
			case \Change\Db\ScalarType::DECIMAL :
				if ($value !== null)
				{
					return floatval($value);
				}
				break;
			case \Change\Db\ScalarType::DATETIME :
				if ($value !== null)
				{
					return \DateTime::createFromFormat('Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
				}
				break;
		}
		return $value;
	}
}