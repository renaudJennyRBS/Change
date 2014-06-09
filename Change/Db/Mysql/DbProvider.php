<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	 * @var null|array
	 */
	protected $registerShutDown = null;
	
	/**
	 * @return string
	 */
	public function getType()
	{
		return 'mysql';
	}

	/**
	 * @return string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return array('Db', 'Db.Mysql');
	}

	/**
	 * @param \PDO|null $driver
	 * @return $this
	 */
	public function setDriver($driver)
	{
		if ($this->m_driver)
		{
			if ($this->inTransaction)
			{
				$this->m_driver->commit();
			}
		}

		$this->m_driver = $driver;
		if ($driver === null)
		{
			$duration = microtime(true) - $this->timers['init'];
			$this->timers['duration'] = $duration;
		}
		else
		{
			if ($this->inTransaction)
			{
				$this->m_driver->beginTransaction();
			}

			if ($this->registerShutDown === null)
			{
				$this->registerShutDown = array($this, "closeConnection");
				register_shutdown_function($this->registerShutDown);
			}
		}
		return $this;
	}
	
	/**
	 * @return \PDO
	 */
	public function getDriver()
	{
		if ($this->m_driver === null)
		{
			$this->setDriver($this->getConnection($this->connectionInfos));
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
	 * @param \ArrayObject $connectionInfos
	 * @throws \RuntimeException
	 * @return \PDO
	 */
	public function getConnection($connectionInfos)
	{
		$protocol = 'mysql';
		$dsnOptions = array();
		
        if (isset($connectionInfos['url']))
        {
            $url = $connectionInfos['url'];

			// If the field starts with 'ENV:', the following environment variable is used to configure MySQL
			if (\strpos($url, 'ENV:') === 0)
			{
				$mysql_url_var_name = \substr($url, 4);
				$envUrl = getenv($mysql_url_var_name);
				if ($envUrl === false)
				{
					throw new \RuntimeException('Environment variable defined in database configuration is not set.', 999999);
				}
				$url = $envUrl;
			}

			$parsed_url = \parse_url($url);
			if ($parsed_url == false)
			{
				throw new \RuntimeException('Database URL is not valid, use mysql://user:password@host:port/dbname.', 999999);
			}

			// Remove the initial '/ or the URL path'
			$database = isset($parsed_url['path']) ? \substr($parsed_url['path'], 1) : null;
			$password = isset($parsed_url['pass']) ? $parsed_url['pass'] : null;
			$username = isset($parsed_url['user']) ? $parsed_url['user'] : null;
			$port = isset($parsed_url['port']) ? $parsed_url['port'] : 3306;
			$host = isset($parsed_url['host']) ? $parsed_url['host'] : 'localhost';
			$dnsOptions[] = 'host=' . $host;
			$dnsOptions[] = 'port=' . $port;
		}
		else
		{
			$database = isset($connectionInfos['database']) ? $connectionInfos['database'] : null;
			$password = isset($connectionInfos['password']) ? $connectionInfos['password'] : null;
			$username = isset($connectionInfos['user']) ? $connectionInfos['user'] : null;

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
		}

        if ($database !== null)
        {
            $dsnOptions[] = 'dbname=' . $database;
        }
        else
        {
            throw new \RuntimeException('Database not defined', 31001);
        }

		$dsn = $protocol . ':' . implode(';', $dsnOptions);

		$options = array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
		$pdo = new \PDO($dsn, $username, $password, $options);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
		return $pdo;
	}

	/**
	 * @return void
	 */
	public function closeConnection()
	{
		if ($this->schemaManager)
		{
			$this->schemaManager->closeConnection();
		}
		$this->setDriver(null);
		$this->getLogging()->info('Close Connection: (S: ' . $this->timers['select'] . ', IUD: ' . $this->timers['exec'] . ')');
		$this->timers['exec'] = $this->timers['select'] = 0;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Db\Mysql\SchemaManager
	 */
	public function getSchemaManager()
	{
		if ($this->schemaManager === null)
		{
			if ($this->inTransaction)
			{
				throw new \RuntimeException('SchemaManager is not available during transaction', 999999);
			}
			$this->closeConnection();
			$this->schemaManager = new SchemaManager($this, $this->getLogging());
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
	 * @param \Change\Events\Event $event
	 * @return void
	 */
	public function beginTransaction($event = null)
	{
		if ($event === null || $event->getParam('primary'))
		{
			if ($this->inTransaction())
			{
				debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				$this->getLogging()->warn(get_class($this) . " while already in transaction");
			}
			else
			{
				$this->timers['bt'] = microtime(true);
				$this->inTransaction = true;
				if ($this->m_driver)
				{
					$this->m_driver->beginTransaction();
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
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
				$this->inTransaction = false;
				if ($this->m_driver)
				{
					$this->m_driver->commit();
				}
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
	 * @param \Change\Events\Event $event
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
				if ($this->m_driver)
				{
					$this->m_driver->rollBack();
				}
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
