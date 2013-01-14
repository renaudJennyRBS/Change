<?php
namespace Change\Db\Mysql;

/**
 * @name \Change\Db\Mysql\SchemaManager
 */
class SchemaManager implements \Change\Db\InterfaceSchemaManager
{	
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
		$sql = "SELECT `COLUMN_NAME`, `COLUMN_DEFAULT`, `IS_NULLABLE`, `DATA_TYPE`, `COLUMN_TYPE` FROM `information_schema`.`COLUMNS` 
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
	 * @param string $treeName
	 * @return string the SQL statements that where executed
	 */
	public function createTreeTable($treeName)
	{
		$tn = $this->dbProvider->getSqlMapping()->getTreeTableName($treeName);
		$sql = 'CREATE TABLE IF NOT EXISTS `'. $tn .'` (
			`document_id` int(11) NOT NULL default \'0\',
			`parent_id` int(11) NOT NULL default \'0\',
			`node_order` int(11) NOT NULL default \'0\',
			`node_level` int(11) NOT NULL default \'0\',
			`node_path` varchar(255) collate latin1_general_ci NOT NULL default \'/\',
			`children_count` int(11) NOT NULL default \'0\',
			PRIMARY KEY (`document_id`),
			INDEX `tree_node` (`parent_id`, `node_order`)
			) ENGINE=InnoDB CHARACTER SET latin1 COLLATE latin1_general_ci';
	
		$this->execute($sql);
		return $sql . ';' . PHP_EOL;
	}
	
	/**
	 * @param string $treeName
	 * @return string the SQL statements that where executed
	 */
	public function dropTreeTable($treeName)
	{
		$tn = $this->dbProvider->getSqlMapping()->getTreeTableName($treeName);
		$sql = 'DROP TABLE IF EXISTS `'. $tn .'`';
		$this->execute($sql);
		return $sql . ';' . PHP_EOL;
	}
			
	/**
	 * @param string $propertyName
	 * @param string $propertyType
	 * @param string $propertyDbSize
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function getDocumentFieldDefinition($propertyName, $propertyType, $propertyDbSize)
	{
		$fn = $this->dbProvider->getSqlMapping()->getDocumentFieldName($propertyName);
		$typeData = null;
		$nullable = true;
		$defaultValue = null;
		
		if ($propertyName === 'publicationStatus')
		{
			$type = "enum";
			$typeData = "enum('DRAFT', 'VALIDATION', 'PUBLISHABLE', 'INVALID', 'DEACTIVATED', 'FILED')";
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
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 */
	public function createOrAlter($tableDefinition)
	{
		$oldDef = $this->getTableDefinition($tableDefinition->getName());
		if ($oldDef->isValid())
		{
			foreach ($tableDefinition->getFields() as $field)
			{
				/* @var $field \Change\Db\Schema\FieldDefinition */
				$oldField = $oldDef->getField($field->getName());
				if ($oldField)
				{
					if ($field->getType() === 'enum' && $field->getTypeData() != $oldField->getTypeData())
					{
						$sql = 'ALTER TABLE `'.$tableDefinition->getName().'` CHANGE `'.$field->getName().'` `'.$field->getName().'` '. $field->getTypeData() . ($field->getNullable() ? ' NULL' : ' NOT NULL') . ($field->getDefaultValue() !== null ? ' DEFAULT \'' . $field->getDefaultValue() . '\'' : '');
						$this->execute($sql);
					}
				}
				else
				{
					$type = $field->getTypeData() !== null ? $field->getTypeData() : $field->getType();
					$sql = 'ALTER TABLE  `'.$tableDefinition->getName().'` ADD `'.$field->getName().'` '.$type.  ($field->getNullable() ? ' NULL' : ' NOT NULL') . ($field->getDefaultValue() !== null ? ' DEFAULT \'' . $field->getDefaultValue() . '\'' : '');
					$this->execute($sql);
				}
			}
		}
		else
		{
			$sql = 'CREATE TABLE `'.$tableDefinition->getName().'` (';
			
			$parts = array();
			foreach ($tableDefinition->getFields() as $field)
			{
				/* @var $field \Change\Db\Schema\FieldDefinition */
				$type = $field->getTypeData() !== null ? $field->getTypeData() : $field->getType();
				$parts[] = '`'.$field->getName().'` '.$type.  ($field->getNullable() ? ' NULL' : ' NOT NULL') . ($field->getDefaultValue() !== null ? ' DEFAULT \'' . $field->getDefaultValue() . '\'' : '');
			}
			foreach ($tableDefinition->getKeys() as $key)
			{
				/* @var $key \Change\Db\Schema\KeyDefinition */
				if ($key->getPrimary())
				{
					$kf = array();
					foreach ($key->getFields() as $kfield)
					{
						/* @var $kfield \Change\Db\Schema\FieldDefinition */
						$kf[] = '`'.$kfield->getName().'`';
					}
					$parts[] = 'PRIMARY KEY  (' . implode(', ', $kf) . ')';
				}
			}		
			$sql .= implode(', ', $parts)  . ') ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci';
			$this->execute($sql);
		}
	}
}