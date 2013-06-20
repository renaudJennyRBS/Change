<?php
namespace Change\Db;

use Change\Configuration\Configuration;
use Change\Db\Query\AbstractQuery;
use Change\Db\Query\Builder;
use Change\Db\Query\StatementBuilder;
use Change\Logging\Logging;
use Change\Transaction\TransactionManager;

/**
 * @name \Change\Db\DbProvider
 * @api
 */
abstract class DbProvider
{
	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var array
	 */
	protected $connectionInfos;

	/**
	 * @var TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var array
	 */
	protected $timers;

	/**
	 * @var Logging
	 */
	protected $logging;

	/**
	 * @var \Change\Db\SqlMapping
	 */
	protected $sqlMapping;

	/**
	 * @var AbstractQuery
	 */
	protected $builderQueries;

	/**
	 * @var AbstractQuery
	 */
	protected $statementBuilderQueries;

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public abstract function getType();

	/**
	 * @param Configuration $config
	 * @throws \RuntimeException
	 * @return \Change\Db\DbProvider
	 */
	public static function newInstance(Configuration $config)
	{
		$section = $config->getEntry('Change/Db/use', 'default');
		$connectionInfos = $config->getEntry('Change/Db/' . $section, array());
		if (!isset($connectionInfos['dbprovider']))
		{
			throw new \RuntimeException('Missing or incomplete database configuration', 31000);
		}
		$className = $connectionInfos['dbprovider'];
		return new $className($connectionInfos);
	}

	/**
	 * @param array $connectionInfos
	 */
	public function __construct(array $connectionInfos)
	{
		$this->connectionInfos = $connectionInfos;
		$this->timers = array('init' => microtime(true),
			'longTransaction' => isset($connectionInfos['longTransaction']) ? floatval($connectionInfos['longTransaction']) : 0.2);
	}

	/**
	 * @return array
	 */
	public function getConnectionInfos()
	{
		return $this->connectionInfos;
	}

	/**
	 * @param array $connectionInfos
	 */
	public function setConnectionInfos($connectionInfos)
	{
		$this->connectionInfos = $connectionInfos;
	}

	/**
	 * @param AbstractQuery $query
	 */
	public function addBuilderQuery(AbstractQuery $query)
	{
		if ($query->getCachedKey() !== null)
		{
			$this->builderQueries[$query->getCachedKey()] = $query;
		}
	}

	/**
	 * @param null $cacheKey
	 * @return Builder
	 */
	public function getNewQueryBuilder($cacheKey = null)
	{
		$query = ($cacheKey !== null && isset($this->builderQueries[$cacheKey])) ? $this->builderQueries[$cacheKey] : null;
		return new Builder($this, $cacheKey, $query);
	}

	/**
	 * @param AbstractQuery $query
	 */
	public function addStatementBuilderQuery(AbstractQuery $query)
	{
		if ($query->getCachedKey() !== null)
		{
			$this->statementBuilderQueries[$query->getCachedKey()] = $query;
		}
	}

	/**
	 * @param string $cacheKey
	 * @return StatementBuilder
	 */
	public function getNewStatementBuilder($cacheKey = null)
	{
		$query = ($cacheKey !== null
			&& isset($this->statementBuilderQueries[$cacheKey])) ? $this->statementBuilderQueries[$cacheKey] : null;
		return new StatementBuilder($this, $cacheKey, $query);
	}

	public function __destruct()
	{
		unset($this->builderQueries);
		unset($this->statementBuilderQueries);
	}

	/**
	 * @return void
	 */
	public abstract function closeConnection();

	/**
	 * @return \Change\Db\InterfaceSchemaManager
	 */
	public abstract function getSchemaManager();

	/**
	 * @return \Change\Db\SqlMapping
	 */
	public function getSqlMapping()
	{
		if ($this->sqlMapping === null)
		{
			$this->sqlMapping = new SqlMapping();
		}
		return $this->sqlMapping;
	}

	/**
	 * @param Logging $logging
	 */
	public function setLogging(Logging $logging)
	{
		$this->logging = $logging;
	}

	/**
	 * @return Logging
	 */
	public function getLogging()
	{
		return $this->logging;
	}

	/**
	 * @param TransactionManager $transactionManager
	 */
	public function setTransactionManager(TransactionManager $transactionManager)
	{
		$this->transactionManager = $transactionManager;
		if ($transactionManager->started())
		{
			$this->beginTransaction(null);
		}
		$tem = $transactionManager->getEventManager();
		$tem->attach(TransactionManager::EVENT_BEGIN, array($this, 'beginTransaction'));
		$tem->attach(TransactionManager::EVENT_COMMIT, array($this, 'commit'));
		$tem->attach(TransactionManager::EVENT_ROLLBACK, array($this, 'rollBack'));
	}

	/**
	 * @return TransactionManager
	 */
	public function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return void
	 */
	public abstract function beginTransaction($event = null);

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return void
	 */
	public abstract function commit($event);

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return void
	 */
	public abstract function rollBack($event);

	/**
	 * @param string $tableName
	 * @return integer
	 */
	public abstract function getLastInsertId($tableName);

	/**
	 * @api
	 * @param \Change\Db\Query\InterfaceSQLFragment $fragment
	 * @return string
	 */
	public function buildSQLFragment(\Change\Db\Query\InterfaceSQLFragment $fragment)
	{
		return $fragment->toSQL92String();
	}

	/**
	 * @param \Change\Db\Query\SelectQuery $selectQuery
	 * @return array
	 */
	public abstract function getQueryResultsArray(\Change\Db\Query\SelectQuery $selectQuery);

	/**
	 * @param AbstractQuery $query
	 * @return integer
	 */
	public abstract function executeQuery(AbstractQuery $query);

	/**
	 * @param mixed $value
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public abstract function phpToDB($value, $scalarType);

	/**
	 * @param mixed $value
	 * @param integer $scalarType \Change\Db\ScalarType::*
	 * @return mixed
	 */
	public abstract function dbToPhp($value, $scalarType);
}