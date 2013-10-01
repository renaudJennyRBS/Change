<?php
namespace Change\Db\SQLite;

use Change\Db\Query\AbstractQuery;
use Change\Db\Query\InterfaceSQLFragment;
use Change\Db\Query\SelectQuery;
use Change\Db\ScalarType;

/**
 * @name \Change\Db\SQLite\DbProvider
 * @api
 */
class DbProvider extends \Change\Db\DbProvider
{
	/**
	 * @var \Change\Db\SQLite\SchemaManager
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
		return 'sqlite';
	}

	/**
	 * @return string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return array('Db', 'Db.Sqlite');
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
	 * @param array $connectionInfos
	 * @throws \RuntimeException
	 * @return \PDO
	 */
	public function getConnection($connectionInfos)
	{
		$protocol = 'sqlite';
		if (!isset($connectionInfos['database']))
		{
			throw new \RuntimeException('Database not defined', 31001);
		}
		$dsn = $protocol . ':' .$connectionInfos['database'];
		$pdo = new \PDO($dsn);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		return $pdo;
	}
	
	/**
	 * @return void
	 */
	public function closeConnection()
	{
		$this->setDriver(null);
		if ($this->schemaManager)
		{
			$this->schemaManager->closeConnection();
		}
		$this->getLogging()->info('Close Connection: (S: ' . $this->timers['select'] . ', IUD: ' . $this->timers['exec'] . ')');
		$this->timers['exec'] = $this->timers['select'] = 0;
	}
	
	/**
	 * @return \Change\Db\SQLite\SchemaManager
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
	 * @param \Zend\EventManager\Event $event
	 * @return void
	 */
	public function beginTransaction($event = null)
	{
		if ($event === null || $event->getParam('primary'))
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
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return void
	 */
	public function commit($event)
	{
		if ($event && $event->getParam('primary'))
		{
			if (!$this->inTransaction())
			{
				$this->getLogging()->warn(__METHOD__ . " called while not in transaction");
			}
			else
			{
				$this->getDriver()->commit();
				$this->inTransaction = false;
				$duration = round(microtime(true) - $this->timers['bt'], 4);
				$this->getLogging()->info('commit: ' . number_format($duration, 3) . 's');
				if ($duration > $this->timers['longTransaction'])
				{
					$this->getLogging()->warn('Long Transaction detected > ' . $this->timers['longTransaction']);
				}
			}
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return void
	 */
	public function rollBack($event)
	{
		if ($event && $event->getParam('primary'))
		{
			if (!$this->inTransaction())
			{
				$this->getLogging()->warn(__METHOD__ . " called while not in transaction");
			}
			else
			{
				$this->inTransaction = false;
				$this->getDriver()->rollBack();
				$duration = round(microtime(true) - $this->timers['bt'], 4);
				$this->getLogging()->info('rollBack: ' . number_format($duration, 3) . 's');
				if ($duration > $this->timers['longTransaction'])
				{
					$this->getLogging()->warn('Long Transaction detected > ' . $this->timers['longTransaction']);
				}
			}
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
	 * @param InterfaceSQLFragment $fragment
	 * @return string
	 */
	public function buildSQLFragment(InterfaceSQLFragment $fragment)
	{
		$fragmentBuilder = new FragmentBuilder($this);
		return $fragmentBuilder->buildSQLFragment($fragment);
	}

	/**
	 * @param AbstractQuery $query
	 * @return string
	 */
	protected function buildQuery(AbstractQuery $query)
	{
		$fragmentBuilder = new FragmentBuilder($this);
		return $fragmentBuilder->buildQuery($query);
	}

	/**
	 * @param SelectQuery $selectQuery
	 * @return array
	 */
	public function getQueryResultsArray(SelectQuery $selectQuery)
	{
		if ($selectQuery->getCachedSql() === null)
		{
			$selectQuery->setCachedSql($this->buildQuery($selectQuery));
		}
		
		$statement = $this->prepareStatement($selectQuery->getCachedSql());
		foreach ($selectQuery->getParameters() as $parameter)
		{
			/* @var $parameter \Change\Db\Query\Expressions\Parameter */
			$value = $this->phpToDB($selectQuery->getParameterValue($parameter->getName()), $parameter->getType());
			$statement->bindValue($this->buildSQLFragment($parameter), $value);
		}
		$this->timers['select']++;
		$statement->execute();
		return $statement->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * @param AbstractQuery $query
	 * @throws \RuntimeException
	 * @return integer
	 */
	public function executeQuery(AbstractQuery $query)
	{
		if ($this->getReadOnly())
		{
			throw new \RuntimeException('This DB provider is read only!', 999999);
		}
		elseif ($this->getCheckTransactionBeforeWriting() && !$this->inTransaction)
		{
			throw new \RuntimeException('No transaction started!', 999999);
		}

		if ($query->getCachedSql() === null)
		{
			$query->setCachedSql($this->buildQuery($query));
		}
		
		$statement = $this->prepareStatement($query->getCachedSql());
		foreach ($query->getParameters() as $parameter)
		{
			/* @var $parameter \Change\Db\Query\Expressions\Parameter */
			$value = $this->phpToDB($query->getParameterValue($parameter->getName()), $parameter->getType());
			$statement->bindValue($this->buildSQLFragment($parameter), $value);
		}
		$this->timers['exec']++;
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
	 * @param mixed $value
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public function phpToDB($value, $scalarType)
	{
		switch ($scalarType)
		{
			case ScalarType::BOOLEAN :
				return ($value) ? 1 : 0;
			case ScalarType::INTEGER :
				if ($value !== null)
				{
					return intval($value);
				}
				break;
			case ScalarType::DECIMAL :
				if ($value !== null)
				{
					return floatval($value);
				}
				break;
			case ScalarType::DATETIME :
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
			case ScalarType::BOOLEAN :
				return ($value == '1');
			case ScalarType::INTEGER :
				if ($value !== null)
				{
					return intval($value);
				}
				break;
			case ScalarType::DECIMAL :
				if ($value !== null)
				{
					return floatval($value);
				}
				break;
			case ScalarType::DATETIME :
				if ($value !== null)
				{
					return \DateTime::createFromFormat('Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
				}
				break;
		}
		return $value;
	}
}