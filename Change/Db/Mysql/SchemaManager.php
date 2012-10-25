<?php
namespace Change\Db\Mysql;

/**
 * @name \Change\Db\Mysql\SchemaManager
 */
class SchemaManager implements \Change\Db\InterfaceSchemaManager
{
	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;
	
	/**
	 * @var \Change\Db\Mysql\DbProvider
	 */
	protected $dbProvider;
	
	/**
	 * @var \PDO
	 */
	protected $pdo;
	
	/**
	 * @param \Change\Db\Mysql\DbProvider $dbProvider
	 */
	public function __construct(\Change\Db\Mysql\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}
	
	/**
	 * @return \PDO
	 */
	private function getDriver()
	{
		if ($this->pdo === null)
		{
			$this->pdo = $this->dbProvider->getConnection($this->dbProvider->getConnectionInfos());
		}
		return $this->pdo;
	}

	/**
	 * @param string query
	 * @return \PDOStatement
	 * @throws \Exception
	 */
	private function query($query)
	{
		return $this->getDriver()->query($query);
	}
	
	/**
	 * @return string|NULL
	 */
	public function getName()
	{
		$ci = $this->dbProvider->getConnectionInfos();
		return is_array($ci) && isset($ci['database']) ? $ci['database'] : null;
	}
	
	/**
	 * @return boolean
	 */
	function check()
	{
		try
		{
			$this->dbProvider->getConnection($this->dbProvider->getConnectionInfos());
		}
		catch (\PDOException $e)
		{
			\Change\Application::getInstance()->getApplicationServices()->getLogging()->exception($e);
			return false;
		}
		return true;
	}
		
	/**
	 * @param string $sql
	 * @return integer the number of affected rows
	 * @throws \Exception on error
	 */
	public function execute($sql)
	{
		return $this->getDriver()->exec($sql);
	}

	/**
	 * @param string $script
	 * @param boolean $throwOnError
	 * @throws \Exception on error
	 */
	function executeBatch($script, $throwOnError = false)
	{
		foreach(explode(';', $script) as $sql)
		{	
			$sql = trim($sql);
			if (empty($sql))
			{
				continue;
			}
			try
			{
				$this->getDriver()->exec($sql);
			}
			catch (\Exception $e)
			{
				if ($e->getCode() != '42S21' && $throwOnError) //Duplicate column
				{
					throw $e;
				}
			}
		}		
	}
	
	/**
	 * Drop all tables from current configured database
	 */
	public function clearDB()
	{
		foreach ($this->getTables() as $table)
		{
			try
			{
				$this->execute('DROP TABLE `' . $table . '`');
			}
			catch (\Exception $e)
			{
				$this->loggin->warn($e->getMessage());
			}
		}
	}
	
	/**
	 * @return string[]
	 */
	public function getTables()
	{
		$tables = array();
		$sql = "SELECT `TABLE_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = '".$this->getName()."'";
		$stmt = $this->query("SHOW TABLES");
		return $stmt->fetchAll(\PDO::FETCH_COLUMN);
	}
	
	/**
	 * @param string $tableName
	 * @return \Change\Db\Schema\TableDefinition
	 */
	public function getTableDefinition($tableName)
	{
		$tableDef = new \Change\Db\Schema\TableDefinition($tableName);
		$sql = "SELECT `COLUMN_NAME`, `COLUMN_DEFAULT`, `IS_NULLABLE`, `COLUMN_TYPE`, `DATA_TYPE` FROM `information_schema`.`COLUMNS` 
    WHERE `TABLE_SCHEMA` = '".$this->getName()."' AND `TABLE_NAME` = '".$tableName."'";
		$statment = $this->query($sql);	
		foreach ($statment->fetchAll(\PDO::FETCH_NUM) as $row)
		{
			$name = $row[0];
			$defaultValue = $row[1];
			$nullable = $row[2] === 'YES';
			$type = $row[3];
			$typeData = $row[3] != $row[4] ? $row[4] : null;
			$tableDef->addField(new \Change\Db\Schema\FieldDefinition($name, $type, $typeData, $nullable, $defaultValue));
		}
		$statment->closeCursor();
		if ($tableDef->isValid())
		{
			$sql = "SELECT C.`CONSTRAINT_NAME`, C.`CONSTRAINT_TYPE`, F.`COLUMN_NAME` FROM `information_schema`.`TABLE_CONSTRAINTS` AS C  
INNER JOIN `information_schema`.`KEY_COLUMN_USAGE` AS F ON F.`TABLE_SCHEMA` = C.`TABLE_SCHEMA` AND F.`TABLE_NAME` = C.`TABLE_NAME` AND C.`CONSTRAINT_NAME` = F.`CONSTRAINT_NAME`
WHERE C.`TABLE_SCHEMA` = '".$this->getName()."' AND C.`TABLE_NAME`= '".$tableName."' ORDER BY C.`CONSTRAINT_NAME`, F.`ORDINAL_POSITION`";
			$statment = $this->query($sql);
			$k = null;
			foreach ($statment->fetchAll(\PDO::FETCH_NUM) as $row)
			{
				if ($k === null || $k->getName() !== $row[0])
				{
					$k = new \Change\Db\Schema\KeyDefinition();
					$tableDef->addKey($k);
					$k->setName($row[0]);
					$k->setPrimary($row[1] === 'PRIMARY KEY');
				}
				$k->addField($tableDef->getField($row[2]));
			}
		}
		return $tableDef;
	}

	/**
	 * @param string $lang
	 * @return boolean
	 */
	public function addLang($lang)
	{
		$infos = $this->getTableDefinition('f_document');
		$fName = 'label_'.$lang;
		if ($infos->getField($fName) === null)
		{
			$sql = "ALTER TABLE `f_document` ADD `label_$lang` VARCHAR(255) NULL";
			$this->execute($sql);
			return true;
		}
		return false;
	}
	
	/**
	 * @param integer $treeId
	 * @return string the SQL statements that where executed
	 */
	public function createTreeTable($treeId)
	{
		$dropSQL = $this->dropTreeTable($treeId);
		$sql = 'CREATE TABLE IF NOT EXISTS `f_tree_'. $treeId .'` (
			`document_id` int(11) NOT NULL default \'0\',
			`parent_id` int(11) NOT NULL default \'0\',
			`node_order` int(11) NOT NULL default \'0\',
			`node_level` int(11) NOT NULL default \'0\',
			`node_path` varchar(255) collate latin1_general_ci NOT NULL default \'/\',
			`children_count` int(11) NOT NULL default \'0\',
			PRIMARY KEY (`document_id`),
			UNIQUE KEY `tree_node` (`parent_id`, `node_order`),
			UNIQUE KEY `descendant` (`node_level`,`node_order`,`node_path`)
			) ENGINE=InnoDB CHARACTER SET latin1 COLLATE latin1_general_ci';
	
		$this->execute($sql);
		return $dropSQL . $sql . ';' . PHP_EOL;
	}
	
	/**
	 * @param integer $treeId
	 * @return string the SQL statements that where executed
	 */
	public function dropTreeTable($treeId)
	{
		$sql = 'DROP TABLE IF EXISTS `f_tree_'. $treeId .'`';
		$this->execute($sql);
		return $sql . ';' . PHP_EOL;
	}
	
	/**
	 * @param string $documentName
	 * @return string
	 */
	public function getDocumentTableName($documentName)
	{
		return $this->dbProvider->getSqlMapping()->getDocumentTableName($documentName);
	}
	
	/**
	 * @param string $documentTableName
	 * @return string
	 */
	public function getDocumentI18nTableName($documentTableName)
	{
		return $this->dbProvider->getSqlMapping()->getDocumentI18nTableName($documentTableName);
	}	
	
	
	/**
	 * @param string $propertyName
	 * @return string
	 */
	public function getDocumentFieldName($propertyName)
	{
		return $this->dbProvider->getSqlMapping()->getDocumentFieldName($propertyName);
	}	
	
	
	/**
	 * @param string $propertyName
	 * @return string
	 */
	public function getDocumentI18nFieldName($propertyName)
	{
		return $this->dbProvider->getSqlMapping()->getDocumentI18nFieldName($propertyName);
	}	
	
	/**
	 * @param string $propertyName
	 * @param boolean $localized
	 * @param string $propertyType
	 * @param string $propertyDbSize
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function getDocumentFieldDefinition($propertyName, $localized, $propertyType, $propertyDbSize)
	{
		$fn = $localized ? $this->getDocumentI18nFieldName($propertyName) : $this->getDocumentFieldName($propertyName);
		$typeData = null;
		$nullable = true;
		$defaultValue = null;
		
		if ($fn === 'document_publicationstatus')
		{
			$type = "enum";
			$typeData = "enum('DRAFT','CORRECTION','ACTIVE','PUBLISHED','DEACTIVATED','FILED','DEPRECATED','TRASH','WORKFLOW')";
		}
		else
		{
			switch ($propertyType)
			{
				case 'Document' :
					$type = "int";
					$typeData = "int(11)";
					break;
				case 'DocumentArray' :
					$type = "int";
					$typeData = "int(11)";
					$defaultValue = "0";
					break;
				case 'String' :
					$dbSize = intval($propertyDbSize);
					if ($dbSize <= 0 || $dbSize > 255) {$dbSize = 255;}
					$type = "varchar";
					$typeData = "varchar(" . $dbSize . ")";
					break;
				case 'LongString' :
					$type = "text";
					break;
				case 'XML' :
				case 'RichText' :
				case 'JSON' :
					$type = "mediumtext";
					break;
				case 'Lob' :
				case 'Object' :
					$type = "mediumblob";
					break;
				case 'Boolean' :
					$type = "tinyint";
					$typeData = "tinyint(1)";
					$nullable = false;
					$defaultValue = '0';
					break;
				case 'Date' :
				case 'DateTime' :
					$type = "datetime";
					break;
				case 'Float' :
					$type = "double";
					break;
				case 'Decimal' :
					$type = "decimal";
					if (!empty($propertyDbSize) && strpos($propertyDbSize, ','))
					{
						$typeData = "decimal(" . $propertyDbSize . ")";
					}
					else
					{
						$typeData = "decimal(13,4)";
					}
					break;
				case 'Integer' :
				case 'DocumentId' :
					$type = "int";
					$typeData = "int(11)";
					break;
			}
		}
		return new \Change\Db\Schema\FieldDefinition($fn, $type, $typeData, $nullable, $defaultValue);
	}
	
	/**
	 * @return string
	 */
	public function getSysDocumentTableName()
	{
		return 'f_document';
	}
}