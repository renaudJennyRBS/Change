<?php
namespace Change\Db\Mysql;

use Change\Db\Schema\TableDefinition;
use Change\Db\Schema\FieldDefinition;
use Change\Db\Schema\KeyDefinition;

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
	 * @var \Change\Logging\Logging
	 */
	protected $logging;
	
	/**
	 * @var \PDO
	 */
	protected $pdo;
	
	/**
	 * @var string[]
	 */
	protected $tables;


	/**
	 * @param \Change\Db\Mysql\DbProvider $dbProvider
	 * @param \Change\Logging\Logging $logging
	 */
	public function __construct(\Change\Db\Mysql\DbProvider $dbProvider, \Change\Logging\Logging $logging)
	{
		$this->setDbProvider($dbProvider);
		$this->setLogging($logging);
	}
	
	/**
	 * @return \Change\Db\Mysql\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\Db\Mysql\DbProvider $dbProvider
	 */
	public function setDbProvider(\Change\Db\Mysql\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	public function getLogging()
	{
		return $this->logging;
	}

	/**
	 * @param \Change\Logging\Logging $logging
	 */
	public function setLogging(\Change\Logging\Logging $logging)
	{
		$this->logging = $logging;
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
	 * @param string $query
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
			$this->getLogging()->exception($e);
			return false;
		}
		return true;
	}

	/**
	 * @return void
	 */
	public function closeConnection()
	{
		$this->pdo = null;
	}

	/**
	 * @param string $sql
	 * @return integer the number of affected rows
	 * @throws \Exception on error
	 */
	public function execute($sql)
	{
		$this->logging->info(__METHOD__ . ': ' . $sql);
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
			$this->getDriver()->exec($sql);
		}		
	}
	
	/**
	 * Drop all tables from current configured database
	 */
	public function clearDB()
	{
		$tables = $this->getTableNames();
		if (count($tables))
		{
			foreach ($this->getTableNames() as $table)
			{
				try
				{
					$this->execute('DROP TABLE `' . $table . '`');
				}
				catch (\Exception $e)
				{
					$this->logging->warn($e->getMessage());
				}
			}
		}
		$this->tables = null;
	}
	
	/**
	 * @return string[]
	 */
	public function getTableNames()
	{
		if ($this->tables === null)
		{
			$sql = "SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '".$this->getName()."'";
			$stmt = $this->query($sql);
			$this->tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		}
		return $this->tables;
	}
	
	/**
	 * @param string $tableName
	 * @return \Change\Db\Schema\TableDefinition|null
	 */
	public function getTableDefinition($tableName)
	{
		$tableDef = null;
		$sql = "SELECT `COLUMN_NAME`, `COLUMN_DEFAULT`, `IS_NULLABLE`, `DATA_TYPE`, `COLUMN_TYPE`, `CHARACTER_MAXIMUM_LENGTH`, `NUMERIC_PRECISION`, `NUMERIC_SCALE`, `EXTRA` FROM `information_schema`.`COLUMNS` 
			WHERE `TABLE_SCHEMA` = '".$this->getName()."' AND `TABLE_NAME` = '".$tableName."' ORDER BY `ORDINAL_POSITION`";
		$statement = $this->query($sql);
		foreach ($statement->fetchAll(\PDO::FETCH_NUM) as $row)
		{
			if ($tableDef === null)
			{
				$tableDef = new TableDefinition($tableName);
			}
			list($name, $defaultValue, $nullable, $dataType, $ctype, $maxLength, $precision, $scale, $extra) = $row;
			$fd = new FieldDefinition($name);
			switch ($dataType) 
			{
				case 'int':
				case 'bigint':
				case 'smallint':
					$type = FieldDefinition::INTEGER;
					break;
				case 'tinyint':
					$type = FieldDefinition::SMALLINT;
					break;
				case 'double':
				case 'float':
					$type = FieldDefinition::FLOAT;
					break;
				case 'decimal':
					$type = FieldDefinition::DECIMAL;
					break;
				case 'datetime':
					$type = FieldDefinition::DATE;
					break;
				case 'timestamp':
					$type = FieldDefinition::TIMESTAMP;
					break;
				case 'varchar':
					$type = FieldDefinition::VARCHAR;
					break;
				case 'char':
					$type = FieldDefinition::CHAR;
					break;
				case 'enum':
					$type = FieldDefinition::ENUM;
					$values = explode('\',\'', substr($ctype, 6, strlen($ctype) - 8));
					$fd->setOption('VALUES', $values);
					break;
				case 'mediumblob':
				case 'blob':
				case 'longblob':
					$type = FieldDefinition::LOB;
					break;
				case 'mediumtext':
				case 'text':
				case 'longtext':
					$type = FieldDefinition::TEXT;
					break;	
				default:
					$type = FieldDefinition::LOB;
					break;
			}
			$fd->setType($type);
			if ($precision !== null)
			{
				$fd->setPrecision($precision);
			}
			if ($scale !== null)
			{
				$fd->setScale($scale);
			}
			if ($maxLength !== null)
			{
				$fd->setLength($maxLength);
			}
			$fd->setNullable($nullable === 'YES');
			$fd->setDefaultValue($defaultValue);
			if ($extra === 'auto_increment')
			{
				$fd->setAutoNumber(true);
			}
			
			$tableDef->addField($fd);
		}
		$statement->closeCursor();
		
		if ($tableDef)
		{
			$sql = "SELECT C.`CONSTRAINT_NAME`, C.`CONSTRAINT_TYPE`, F.`COLUMN_NAME` FROM `information_schema`.`TABLE_CONSTRAINTS` AS C  
INNER JOIN `information_schema`.`KEY_COLUMN_USAGE` AS F ON F.`CONSTRAINT_NAME` = C.`CONSTRAINT_NAME`
WHERE C.`TABLE_SCHEMA` = '".$this->getName()."' AND C.`TABLE_NAME`= '".$tableName."' AND F.`TABLE_SCHEMA` = '".$this->getName()."' AND F.`TABLE_NAME`= '".$tableName."' ORDER BY C.`CONSTRAINT_NAME`, F.`ORDINAL_POSITION`";
			$statement = $this->query($sql);

			/* @var $k \Change\Db\Schema\KeyDefinition */
			$k = null;
			foreach ($statement->fetchAll(\PDO::FETCH_NUM) as $row)
			{
				if ($k === null || $k->getName() !== $row[0])
				{
					$k = new \Change\Db\Schema\KeyDefinition();
					$tableDef->addKey($k);
					$k->setName($row[0]);
					if ($row[1] === 'PRIMARY KEY')
					{
						$k->setType(\Change\Db\Schema\KeyDefinition::PRIMARY);
					}
					elseif ($row[1] === 'UNIQUE')
					{
						$k->setType(\Change\Db\Schema\KeyDefinition::UNIQUE);
					}
					else
					{
						$k->setType(\Change\Db\Schema\KeyDefinition::INDEX);
					}
				}
				$k->addField($tableDef->getField($row[2]));
			}
		}
		
		return $tableDef;
	}
	
	/**
	 * @param string $tableName
	 * @return \Change\Db\Schema\TableDefinition
	 */
	public function newTableDefinition($tableName)
	{
		$td = new TableDefinition($tableName);
		$td->setOptions(array('ENGINE' => 'InnoDB', 'CHARSET' => 'utf8', 'COLLATION' => 'utf8_unicode_ci'));
		return $td;
	}

	/**
	 * @param integer $scalarType
	 * @param array $fieldDbOptions
	 * @param array $defaultDbOptions
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function getFieldDbOptions($scalarType, array $fieldDbOptions = null, array $defaultDbOptions = null)
	{
		switch ($scalarType)
		{
			case \Change\Db\ScalarType::STRING:
				if (!isset($fieldDbOptions['length']))
				{
					$fieldDbOptions['length'] = ($defaultDbOptions === null) ? 255 : intval($defaultDbOptions['length']);
				}
				else
				{
					$fieldDbOptions['length'] = intval($fieldDbOptions['length']);
				}
				return $fieldDbOptions;

			case \Change\Db\ScalarType::TEXT:
			case \Change\Db\ScalarType::LOB:
				if (!isset($fieldDbOptions['length']))
				{
					$fieldDbOptions['length'] = 16777215;
				}
				else
				{
					$fieldDbOptions['length'] = intval($fieldDbOptions['length']);
				}
				return $fieldDbOptions;
			case \Change\Db\ScalarType::DECIMAL:
				if (!isset($fieldDbOptions['precision']))
				{
					$fieldDbOptions['precision'] = ($defaultDbOptions === null) ? 13 : intval($defaultDbOptions['precision']);
				}
				else
				{
					$fieldDbOptions['precision'] = intval($fieldDbOptions['precision']);
				}
				if (!isset($fieldDbOptions['scale']))
				{
					$fieldDbOptions['scale'] = ($defaultDbOptions === null) ? 4 : intval($defaultDbOptions['scale']);
				}
				else
				{
					$fieldDbOptions['scale'] = intval($fieldDbOptions['scale']);
				}
				return $fieldDbOptions;

			case \Change\Db\ScalarType::BOOLEAN:
			case \Change\Db\ScalarType::INTEGER:
				if (!isset($fieldDbOptions['precision']))
				{
					$def = $scalarType === \Change\Db\ScalarType::BOOLEAN ? 3 : 10;
					$fieldDbOptions['precision'] = ($defaultDbOptions === null) ? $def : intval($defaultDbOptions['precision']);
				}
				$fieldDbOptions['scale'] = 0;
				return $fieldDbOptions;

			case \Change\Db\ScalarType::DATETIME:
				if (!is_array($fieldDbOptions))
				{
					$fieldDbOptions = is_array($defaultDbOptions) ? $defaultDbOptions : array();
				}
				return $fieldDbOptions;

			default:
				throw new \InvalidArgumentException('Invalid Field type: ' . $scalarType, 41000);
		}
	}

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @throws \InvalidArgumentException
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newEnumFieldDefinition($name, array $dbOptions)
	{
		if (!isset($dbOptions['VALUES']) || !is_array($dbOptions['VALUES']) || count($dbOptions['VALUES']) == 0)
		{
			throw new \InvalidArgumentException('Invalid Enum values', 41001);
		}
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::ENUM);
		$fd->setOption('VALUES', $dbOptions['VALUES']);
		return $fd;
	}

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newCharFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::CHAR);
		$dbOptions = $this->getFieldDbOptions(\Change\Db\ScalarType::STRING, $dbOptions);
		$fd->setLength($dbOptions['length']);
		return $fd;
	}
	
	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newVarCharFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::VARCHAR);
		$dbOptions = $this->getFieldDbOptions(\Change\Db\ScalarType::STRING, $dbOptions);
		$fd->setLength($dbOptions['length']);
		return $fd;
	}

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newNumericFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::DECIMAL);
		$dbOptions = $this->getFieldDbOptions(\Change\Db\ScalarType::DECIMAL, $dbOptions);
		$fd->setPrecision($dbOptions['precision']);
		$fd->setScale($dbOptions['scale']);
		return $fd;
	}
	
	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newBooleanFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::SMALLINT);
		$dbOptions = $this->getFieldDbOptions(\Change\Db\ScalarType::BOOLEAN, $dbOptions);
		$fd->setPrecision($dbOptions['precision']);
		$fd->setScale($dbOptions['scale']);
		$fd->setNullable(false);
		$fd->setDefaultValue(0);
		return $fd;
	}
	
	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newIntegerFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::INTEGER);
		$dbOptions = $this->getFieldDbOptions(\Change\Db\ScalarType::INTEGER, $dbOptions);
		$fd->setPrecision($dbOptions['precision']);
		$fd->setScale($dbOptions['scale']);
		return $fd;
	}
	
	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newFloatFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::FLOAT);
		return $fd;
	}

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newTextFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::TEXT);
		$dbOptions = $this->getFieldDbOptions(\Change\Db\ScalarType::TEXT, $dbOptions);
		$fd->setLength($dbOptions['length']);
		return $fd;
	}

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newLobFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::LOB);
		$dbOptions = $this->getFieldDbOptions(\Change\Db\ScalarType::LOB, $dbOptions);
		$fd->setLength($dbOptions['length']);
		return $fd;
	}

	
	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newDateFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::DATE);
		return $fd;
	}
	
	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newTimeStampFieldDefinition($name, array $dbOptions = null)
	{
		$fd = new FieldDefinition($name);
		$fd->setType(FieldDefinition::TIMESTAMP);
		$fd->setDefaultValue('CURRENT_TIMESTAMP');
		$fd->setNullable(false);
		return $fd;
	}

	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @return string SQL definition
	 */
	public function createOrAlterTable(\Change\Db\Schema\TableDefinition $tableDefinition)
	{
		if (in_array($tableDefinition->getName(), $this->getTableNames()))
		{
			$oldDef = $this->getTableDefinition($tableDefinition->getName());
			if ($oldDef)
			{
				return $this->alterTable($tableDefinition, $oldDef);
			}
		}
		return $this->createTable($tableDefinition);
	}

	/**
	 * @param FieldDefinition $fieldDefinition
	 * @throws \RuntimeException
	 * @return string
	 */
	protected function generateColumnType(FieldDefinition $fieldDefinition)
	{
		switch ($fieldDefinition->getType())
		{
			case FieldDefinition::CHAR:
				$type = 'char('.$fieldDefinition->getLength().')';
				break;
			case FieldDefinition::VARCHAR:
				$type = 'varchar('.$fieldDefinition->getLength().')';
				break;
			case FieldDefinition::DATE:
				$type = 'datetime';
				break;
			case FieldDefinition::TIMESTAMP:
				$type = 'timestamp';
				break;
			case FieldDefinition::DECIMAL:
				$type = 'decimal('.$fieldDefinition->getPrecision(). ','. $fieldDefinition->getScale().')';
				break;
			case FieldDefinition::ENUM:
				$values = $fieldDefinition->getOption('VALUES');
				if (!is_array($values) || count($values) == 0)
				{
					throw new \RuntimeException('Invalid Enum Values', 41001);
				}
				$type = 'enum(\''.implode('\',\'', $values).'\')';
				break;
			case FieldDefinition::FLOAT:
				$type = 'double';
				break;
			case FieldDefinition::INTEGER:
				$type = 'int(11)';
				break;
			case FieldDefinition::SMALLINT:
				$type = 'tinyint(1)';
				break;
			case FieldDefinition::LOB:
				$type = 'mediumblob';
				break;
			case FieldDefinition::TEXT:
				$type = 'mediumtext';
				break;
			default:
				throw new \RuntimeException('Invalid Field Definition type: ' . $fieldDefinition->getType(), 41002);
				break;
		}
	
		if (!$fieldDefinition->getNullable())
		{
			$type .= ' NOT NULL';
		}
	
		if ($fieldDefinition->getDefaultValue() !== null)
		{
			if ($fieldDefinition->getDefaultValue() === 'CURRENT_TIMESTAMP')
			{
				$type .= ' DEFAULT CURRENT_TIMESTAMP';
			}
			else
			{
				$type .= ' DEFAULT \'' . $fieldDefinition->getDefaultValue() . '\'';
			}
		}
		elseif ($fieldDefinition->getAutoNumber())
		{
			$type .= ' auto_increment';
		}
		elseif ($fieldDefinition->getNullable())
		{
			$type .= ' NULL';
		}
		return $type;
	}
	
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @param \Change\Db\Schema\TableDefinition $oldDef
	 * @return string SQL definition
	 */
	public function alterTable(\Change\Db\Schema\TableDefinition $tableDefinition, \Change\Db\Schema\TableDefinition $oldDef)
	{
		$sqls = array();
		foreach ($tableDefinition->getFields() as $field)
		{
			/* @var $field \Change\Db\Schema\FieldDefinition */
			$oldField = $oldDef->getField($field->getName());
			$type = $this->generateColumnType($field);
			if ($oldField)
			{
				$oldType = $this->generateColumnType($oldField);
				if ($type != $oldType)
				{
					$sql = 'ALTER TABLE `'.$tableDefinition->getName().'` CHANGE `'.$field->getName().'` `'.$field->getName().'` '.$type;
					$sqls[] = $sql;
					$this->execute($sql);
				}
			}
			else
			{
				$sql = 'ALTER TABLE  `'.$tableDefinition->getName().'` ADD `'.$field->getName().'` '.$type;
				$sqls[] = $sql;
				$this->execute($sql);
			}
		}
		return implode(';'.PHP_EOL, $sqls);
	}
	
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @return string SQL definition
	 */
	public function createTable(\Change\Db\Schema\TableDefinition $tableDefinition)
	{
		$sql = 'CREATE TABLE `'.$tableDefinition->getName().'` (';	
		$parts = array();
		foreach ($tableDefinition->getFields() as $field)
		{
			/* @var $field \Change\Db\Schema\FieldDefinition */
			
			$type = $this->generateColumnType($field);
			$parts[] = '`'.$field->getName().'` '. $type;
		}
		foreach ($tableDefinition->getKeys() as $key)
		{
			/* @var $key \Change\Db\Schema\KeyDefinition */
			$kf = array();
			foreach ($key->getFields() as $kfield)
			{
				/* @var $kfield \Change\Db\Schema\FieldDefinition */
				$kf[] = '`'.$kfield->getName().'`';
			}
			
			if ($key->isPrimary())
			{
				$parts[] = 'PRIMARY KEY  (' . implode(', ', $kf) . ')';
			}
			elseif ($key->isUnique())
			{
				$parts[] = 'UNIQUE KEY `'.$key->getName().'` (' . implode(', ', $kf) . ')';
			}
			else
			{
				$parts[] = 'INDEX `'.$key->getName().'` (' . implode(', ', $kf) . ')';
			}
			
		}
		$engine = $tableDefinition->getOption('ENGINE');
		if ($engine) {$engine = ' ENGINE= ' .$engine;}
		
		$startAuto = $tableDefinition->getOption('AUTONUMBER');
		if ($startAuto) {$startAuto = ' AUTO_INCREMENT='.$startAuto;}
		
		$charset = $tableDefinition->getOption('CHARSET');
		if ($charset) {$charset = ' CHARACTER SET '.$charset;}
		
		$collation = $tableDefinition->getOption('COLLATION');
		if ($collation) {$collation = ' COLLATE '.$collation;}
				
		$sql .= implode(', ', $parts)  . ')' . $engine . $startAuto . $charset. $collation;
		$this->execute($sql);	
		if (is_array($this->tables) && !in_array($tableDefinition->getName(), $this->tables))
		{
			$this->tables[] = $tableDefinition->getName();
		}
		return $sql;
	}
	
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @return string SQL definition
	 */
	public function dropTable(\Change\Db\Schema\TableDefinition $tableDefinition)
	{
		$sql = 'DROP TABLE IF EXISTS `'. $tableDefinition->getName() .'`';
		if (is_array($this->tables))
		{
			$this->tables = array_values(array_diff($this->tables, array($tableDefinition->getName())));
		}		
		$this->execute($sql);
		return $sql;
	}

	/**
	 * @return \Change\Db\Schema\SchemaDefinition
	 */
	public function getSystemSchema()
	{
		return new \Change\Db\Mysql\Schema($this);
	}
}