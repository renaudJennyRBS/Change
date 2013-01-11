<?php
namespace Change\Db;

/**
 * @name \Change\Db\DbProvider
 * @method \Change\Db\DbProvider getInstance()
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
	 * @var array
	 */
	protected $timers;
	
	/**
	 * @var integer
	 */
	protected $transactionCount = 0;
	
	/**
	 * @var boolean
	 */
	protected $transactionDirty = false;
	
	/**
	 * @var boolean
	 */
	protected $inTransaction = false;
	
	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;
	
	/**
	 * @var \Change\Db\SqlMapping
	 */
	protected $sqlMapping;
		
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
	 * @param \Change\Configuration\Configuration $config
	 * @param \Change\Logging\Logging $logging
	 * @throws \RuntimeException
	 * @return \Change\Db\DbProvider
	 */
	public static function newInstance(\Change\Configuration\Configuration $config, \Change\Logging\Logging $logging)
	{
		$connectionInfos = $config->getEntry('databases/default', array());
		if (!isset($connectionInfos['dbprovider']))
		{
			throw new \RuntimeException('Missing or incomplete database configuration');
		}
		$className = $connectionInfos['dbprovider'];
		return new $className($connectionInfos, $logging);
	}
	
	/**
	 * @param array $connectionInfos
	 * @param \Change\Logging\Logging $logging
	 */
	public function __construct(array $connectionInfos, \Change\Logging\Logging $logging)
	{
		$this->connectionInfos = $connectionInfos;
		$this->logging = $logging;
		$this->timers = array('init' => microtime(true), 'longTransaction' => isset($connectionInfos['longTransaction']) ? floatval($connectionInfos['longTransaction']) : 0.2);
	}	
	
	public function __destruct()
	{
		if ($this->hasTransaction())
		{
			$this->logging->warn(__METHOD__ . ' called while active transaction (' . $this->transactionCount . ')');
		}
	}
	
	/**
	 * @throws \Exception
	 */
	protected final function checkDirty()
	{
		if ($this->transactionDirty)
		{
			throw new \Exception('Transaction is dirty');
		}
	}
	
	/**
	 * @return void
	 */
	public function beginTransaction()
	{
		$this->checkDirty();
		if ($this->transactionCount == 0)
		{
			$this->transactionCount++;
			if ($this->inTransaction)
			{

				$this->logging->warn(get_class($this) . " while already in transaction");
			}
			else
			{
				$this->timers['bt'] = microtime(true);
				$this->beginTransactionInternal();
				$this->inTransaction = true;
				//TODO Old class Usage
				\indexer_IndexService::getInstance()->beginIndexTransaction();
			}
		}
		else
		{
			$embededTransaction = intval(\Change\Application::getInstance()->getConfiguration()->getEntry('databases/default/embededTransaction', '5'));
			$this->transactionCount++;
			if ($this->transactionCount > $embededTransaction)
			{
				$this->logging->warn('embeded transaction: ' . $this->transactionCount);
			}
		}
	}
	
	/**
	 * @param boolean $isolatedWrite make sense in the context of read-write separated database. Set to true if the next client request does not care about the data you wrote. It will then perform reads on read database.
	 * @throws Exception if bad transaction count
	 * @return void
	 */
	public function commit($isolatedWrite = false)
	{
		$this->checkDirty();
		if ($this->transactionCount <= 0)
		{
			throw new \Exception('commit-bad-transaction-count ('.$this->transactionCount.')');
		}
		if ($this->transactionCount == 1)
		{
			if (!$this->inTransaction)
			{
				$this->logging->warn("PersistentProvider->commit() called while not in transaction");
			}
			else
			{
				$this->commitInternal();
				$duration = round(microtime(true) - $this->timers['bt'], 4);
				if ($duration > $this->timers['longTransaction'])
				{
					$this->logging->warn('Long Transaction detected '.  number_format($duration, 3) . 's > ' . $this->timers['longTransaction']);
				}
				$this->inTransaction = false;		
				$this->beginTransactionInternal();
				//TODO Old class Usage
				\indexer_IndexService::getInstance()->commitIndex();
				$this->commitInternal();
			}
		}
		$this->transactionCount--;
	}
	
	/**
	 * Cancel transaction.
	 * @param \Exception $e
	 * @throws \BaseException('rollback-bad-transaction-count') if rollback called while no transaction
	 * @throws \Change\Db\Exception\TransactionCancelledException on embeded transaction
	 * @return Exception the given exception so it is easy to throw it
	 */
	public function rollBack($e = null)
	{
		$this->logging->warn('Provider->rollBack called');
		if ($this->transactionCount == 0)
		{
			$this->logging->warn('Provider->rollBack() => bad transaction count (no transaction)');
			throw new \Exception('rollback-bad-transaction-count');
		}
		$this->transactionCount--;
		
		if (!$this->transactionDirty)
		{
			$this->transactionDirty = true;
			if (!$this->inTransaction)
			{
				$this->logging->warn("Provider->rollBack() called while not in transaction");
			}
			else
			{
				$this->clearDocumentCache();
				//TODO Old class Usage
				\indexer_IndexService::getInstance()->rollBackIndex();
				$this->rollBackInternal();
				$this->inTransaction = false;
			}
		}
		
		if ($this->transactionCount == 0)
		{
			$this->transactionDirty = false;
		}
		else
		{
			if (!($e instanceof \Change\Db\Exception\TransactionCancelledException))
			{
				$e = new \Change\Db\Exception\TransactionCancelledException($e);
			}
			throw $e;
		}
		return ($e instanceof \Change\Db\Exception\TransactionCancelledException) ? $e->getPrevious() : $e;
	}
	
	/**
	 * @return boolean
	 */
	public function hasTransaction()
	{
		return $this->transactionCount > 0;
	}
	
	/**
	 * @return boolean
	 */
	public function isTransactionDirty()
	{
		return $this->transactionDirty;
	}
	
	/**
	 * @return array
	 */
	public function getConnectionInfos()
	{
		return $this->connectionInfos;
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
	 * @return void
	 */
	protected abstract function beginTransactionInternal();
	
	/**
	 * @return void
	 */
	protected abstract function commitInternal();
	
	/**
	 * @return void
	 */
	protected abstract function rollBackInternal();
	
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
	 * @param \Change\Db\Query\AbstractQuery $query
	 * @return integer
	 */
	public abstract function executeQuery(\Change\Db\Query\AbstractQuery $query);
	
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