<?php
namespace Change\Db\Mysql;

/**
 * @name \Change\Db\Mysql\DbProvider
 * @method \Change\Db\Mysql\Provider getInstance()
 */
class DbProvider extends \Change\Db\DbProvider
{
	/**
	 * @var \Change\Db\Mysql\Statment
	 */
	protected $currentStatment = null;

	/**
	 * @var \Change\Db\Mysql\SqlMapping
	 */
	protected $sqlMapping;

	/**
	 * @return \Change\Db\Mysql\SqlMapping
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
	 * @return string
	 */
	protected function getI18nSuffix()
	{
		return $this->getSqlMapping()->getI18nSuffix();
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'mysql';
	}

	/**
	 * @var \PDO instance provided by PDODatabase
	 */
	private $m_driver = null;

	/**
	 * @param \PDO $driver
	 */
	public function setDriver($driver)
	{
		$this->m_driver = $driver;
		if ($driver === null)
		{
			$duration = microtime(true) - $this->timers['init'];
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
	 * @param array<String, String> $connectionInfos
	 * @return \PDO
	 */
	public function getConnection($connectionInfos)
	{
		$protocol = 'mysql';
		$dsnOptions = array();

		$database = isset($connectionInfos['database']) ? $connectionInfos['database'] : null;
		$password = isset($connectionInfos['password']) ? $connectionInfos['password'] : null;
		$username = isset($connectionInfos['user']) ? $connectionInfos['user'] : null;

		$dsn = $protocol.':';

		if ($database !== null)
		{
			$dsnOptions[] = 'dbname='.$database;
		}
		$unix_socket = isset($connectionInfos['unix_socket']) ? $connectionInfos['unix_socket'] : null;
		if ($unix_socket != null)
		{
			$dsnOptions[] = 'unix_socket='.$unix_socket;
		}
		else
		{
			$host = isset($connectionInfos['host']) ? $connectionInfos['host'] : 'localhost';
			$dsnOptions[] = 'host='.$host;
			$port = isset($connectionInfos['port']) ? $connectionInfos['port'] : 3306;
			$dsnOptions[] = 'port='.$port;
		}

		$dsn = $protocol.':'.join(';', $dsnOptions);

		$options = array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'") ;
		$pdo = new \PDO($dsn, $username, $password, $options);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		return $pdo;
	}

	/**
	 * @param \Change\Db\Mysql\Statment $statment|null
	 */
	protected function setCurrentStatment($statment)
	{
		if ($this->currentStatment instanceof Statment)
		{
			$this->currentStatment->close();
			$this->currentStatment = null;
		}
		$this->currentStatment = $statment;
	}

	/**
	 * @return void
	 */
	public function closeConnection()
	{
		$this->setCurrentStatment(null);
		$this->setDriver(null);
	}

	/**
	 * @param string $sql
	 * @param \Change\Db\StatmentParameter[] $parameters
	 * @return \Change\Db\Mysql\Statment
	 */
	public function createNewStatment($sql, $parameters = null)
	{
		return $this->prepareStatement($sql, $parameters);
	}

	/**
	 * @var \Change\Db\Mysql\SchemaManager
	 */
	protected $schemaManager = null;

	
	/**
	 * @return \Change\Db\Mysql\SchemaManager
	 */
	public function getSchemaManager()
	{
		if ($this->schemaManager === null)
		{
			$this->schemaManager = new SchemaManager($this);
		}
		return $this->schemaManager;
	}	
	
	protected function beginTransactionInternal()
	{
		$this->setCurrentStatment(null);
		$this->getDriver()->beginTransaction();
	}
	
	protected function commitInternal()
	{
		$this->setCurrentStatment(null);
		$this->getDriver()->commit();
	}
	
	protected function rollBackInternal()
	{
		$this->getDriver()->rollBack();
	}
	
	/**
	 * @param string $sql
	 * @param \Change\Db\StatmentParameter[] $parameters
	 * @return \Change\Db\Mysql\Statment
	 */
	public function prepareStatement($sql, $parameters = null)
	{
		$this->setCurrentStatment(null);
		$stmt = new Statment($this, $sql, $parameters);
		$this->setCurrentStatment($stmt);
		return $stmt;
	}
	
	/**
	 * @param Statment $stmt
	 */
	public function executeStatement($stmt)
	{
		if (!$stmt->execute())
		{
			$this->showError($stmt);
		}
	}
	
	/**
	 * @param Statment $statement
	 */
	protected function showError($statement = null)
	{
		if ($statement !== null)
		{
			$msg = $statement->getErrorMessage();
		}
		else
		{
			$msg = "Driver ERROR Code (". $this->errorCode() . ") : " . $this->errorInfo();
		}
		throw new \Exception($msg);
	}
	
	/**
	 * Return a translated text or null
	 * @param string $lcid
	 * @param string $id
	 * @param string $keyPath
	 * @return array[$content, $format]
	 */
	public function translate($lcid, $id, $keyPath)
	{
		$stmt = $this->prepareStatement('SELECT `content`, `format` FROM `f_locale` WHERE `lang` = :lang AND `id` = :id AND `key_path` = :key_path');
		$stmt->bindValue(':lang', $lcid, \PDO::PARAM_STR);
		$stmt->bindValue(':id', $id, \PDO::PARAM_STR);
		$stmt->bindValue(':key_path', $keyPath, \PDO::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		if (count($results) > 0)
		{
			$content = $results[0]['content'];
			if ($content == NULL) {$content = '';}
			return array($content, $results[0]['format']);
		}
		return array(null, null);
	}
	
	/**
	 * @param integer $documentId
	 * @param string $rootModelName
	 * @return array<modelName, treeId>|null
	 */
	public function getDocumentInitializeInfos($documentId, $rootModelName = null)
	{
		if ($rootModelName)
		{
			$table = $this->getSqlMapping()->getDocumentTableName($rootModelName);
			$stmt = $this->prepareStatement("SELECT `f`.`document_model`, `f`.`treeid` FROM `$table` AS `d` INNER JOIN `f_document` AS `f` USING `document_id` WHERE `d`.`document_id` = :id");
		}
		else
		{
			$stmt = $this->prepareStatement("SELECT `f`.`document_model`, `f`.`treeid` FROM  `f_document` AS `f` WHERE `f`.`document_id` = :id");
		}
		$stmt->bindValue(':id', $documentId, \PDO::PARAM_INT);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(\PDO::FETCH_NUM);
		$stmt->close();
		return is_array($result) ? $result : null;
	}
	
	/**
	 * @param integer $documentId
	 * @param string $rootModelName
	 * @param array<propertyName => fieldName> $fieldMapping
	 * @return array
	 */
	public function getDocumentProperties($documentId, $rootModelName, $fieldMapping)
	{
		$smap = $this->getSqlMapping();
		$select = array();
		foreach ($fieldMapping as $propertyName => $fieldName)
		{
			/* @var $propertyName => $fieldName unknown_type */
			$select[] = $smap->escapeName($fieldName, 'd', $propertyName);
		}
		$table = $this->getSqlMapping()->getDocumentTableName($rootModelName);
		$stmt = $this->prepareStatement("SELECT "  . implode(', ', $select) . " FROM `$table` AS `d` WHERE `d`.`document_id` = :id");
		$stmt->bindValue(':id', $documentId, \PDO::PARAM_INT);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->close();
		return is_array($result) ? $result : null;
	}
	
	
	/**
	 * @param integer $documentId
	 * @param string $LCID
	 * @param string $rootModelName
	 * @param array<propertyName => fieldName> $fieldMapping
	 * @return array
	 */
	public function getI18nDocumentProperties($documentId, $LCID, $rootModelName, $fieldMapping)
	{
		$smap = $this->getSqlMapping();
		$select = array();
		foreach ($fieldMapping as $propertyName => $fieldName)
		{
			/* @var $propertyName => $fieldName unknown_type */
			$select[] = $smap->escapeName($fieldName, 'd', $propertyName);
		}
		$table = $this->getSqlMapping()->getDocumentI18nTableName($rootModelName);
		$stmt = $this->prepareStatement("SELECT "  . implode(', ', $select) . " FROM `$table` AS `d` WHERE `d`.`document_id` = :id AND `d`.`lcid` = :lcid");
		$stmt->bindValue(':id', $documentId, \PDO::PARAM_INT);
		$stmt->bindValue(':lcid', $LCID, \PDO::PARAM_STR);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->close();
		return is_array($result) ? $result : null;
	}
}