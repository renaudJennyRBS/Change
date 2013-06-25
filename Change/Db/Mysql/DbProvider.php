<?php
namespace Change\Db\Mysql;

use Change\Db\Query\AbstractQuery;
use Change\Db\Query\InterfaceSQLFragment;
use Change\Db\Query\SelectQuery;
use Change\Db\ScalarType;

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
			throw new \RuntimeException('Database not defined', 31001);
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
		if ($this->schemaManager)
		{
			$this->schemaManager->closeConnection();
		}
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
	 * @param \Zend\EventManager\Event $event
	 * @return void
	 */
	public function beginTransaction($event = null)
	{
		if ($event === null || $event->getParam('primary'))
		{
			if ($this->inTransaction())
			{
				debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
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
				$this->logging->warn(__METHOD__ . " called while not in transaction");
			}
			else
			{
				$this->inTransaction = false;
				$this->getDriver()->rollBack();
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
	 * @param AbstractQuery $query
	 * @return integer
	 */
	public function executeQuery(AbstractQuery $query)
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