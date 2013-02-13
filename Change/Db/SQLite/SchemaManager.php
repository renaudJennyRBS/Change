<?php
namespace Change\Db\SQLite;

use Change\Db\Schema\TableDefinition;
use Change\Db\Schema\FieldDefinition;
use Change\Db\Schema\KeyDefinition;

/**
 * @name \Change\Db\SQLite\SchemaManager
 */
class SchemaManager implements \Change\Db\InterfaceSchemaManager
{	
	/**
	 * @var \Change\Db\SQLite\DbProvider
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
	 * @param \Change\Db\SQLite\DbProvider $dbProvider
	 * @param \Change\Logging\Logging $logging
	 */
	public function __construct(\Change\Db\SQLite\DbProvider $dbProvider, \Change\Logging\Logging $logging)
	{
		$this->setDbProvider($dbProvider);
		$this->setLogging($logging);
	}
	
	/**
	 * @return \Change\Db\SQLite\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\Db\SQLite\DbProvider $dbProvider
	 */
	public function setDbProvider(\Change\Db\SQLite\DbProvider $dbProvider)
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
			foreach ($tables as $table)
			{
				try
				{
					$this->execute('DROP TABLE [' . $table . ']');
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
			$sql = "SELECT [name] FROM [sqlite_master] WHERE [type] = 'table' AND [name] NOT LIKE 'sqlite_%'";
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
		$sql = "PRAGMA table_info([".$tableName."])";
		$statement = $this->query($sql);
		foreach ($statement->fetchAll(\PDO::FETCH_NUM) as $row)
		{
			if ($tableDef === null)
			{
				$tableDef = new TableDefinition($tableName);
			}
			list( , $name, $rawType, $notnull, $default_value, $pk) = $row;
			$fd = new FieldDefinition($name);
			if (preg_match('/([a-z]+)(?:\(([0-9,]+)\))?/', $rawType, $matches))
			{
				$dataType = strtolower($matches[1]);
				$size = isset($matches[2]) ? $matches[2] : null;
			}
			else
			{
				$dataType = strtolower($rawType);
				$size = null;
			}

			switch ($dataType)
			{
				case 'int':
				case 'integer':
					$type = FieldDefinition::INTEGER;
					break;
				case 'tinyint':
					$type = FieldDefinition::SMALLINT;
					break;
				case 'float':
				case 'double':
					$type = FieldDefinition::FLOAT;
					break;
				case 'decimal':
					$type = FieldDefinition::DECIMAL;
					break;
				case 'chardate':
					$type = FieldDefinition::DATE;
					break;
				case 'chartimestamp':
					$type = FieldDefinition::TIMESTAMP;
					break;
				case 'varchar':
					$type = FieldDefinition::VARCHAR;
					$fd->setLength(intval($size));
					$size = null;
					break;
				case 'char':
					$type = FieldDefinition::CHAR;
					$fd->setLength(intval($size));
					$size = null;
					break;
				case 'blob':
					$type = FieldDefinition::LOB;
					break;
				case 'text':
					$type = FieldDefinition::TEXT;
					break;	
				default:
					$type = FieldDefinition::LOB;
					break;
			}
			$fd->setType($type);
			if ($size)
			{
				$sp = explode(',', $size);
				$fd->setPrecision(intval($sp[0]));
				$fd->setScale(isset($sp[1])? intval($sp[1]) : null);
			}
			$fd->setNullable($notnull == '0');
			$fd->setDefaultValue($default_value);
			if ($type === FieldDefinition::INTEGER && $pk == '1')
			{
				$fd->setAutoNumber(true);
			}
			$tableDef->addField($fd);
			if ($pk == '1')
			{
				$k = new \Change\Db\Schema\KeyDefinition();
				$tableDef->addKey($k);
				$k->setType(\Change\Db\Schema\KeyDefinition::PRIMARY);
				$k->addField($fd);
			}
		}
		$statement->closeCursor();
		return $tableDef;
	}
	
	/**
	 * @param string $tableName
	 * @return \Change\Db\Schema\TableDefinition
	 */
	public function newTableDefinition($tableName)
	{
		$td = new TableDefinition($tableName);
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
					$def = $scalarType === \Change\Db\ScalarType::BOOLEAN ? 1 : 11;
					$fieldDbOptions['precision'] = ($defaultDbOptions === null) ? $def : intval($defaultDbOptions['precision']);
				}
				return $fieldDbOptions;

			case \Change\Db\ScalarType::TEXT:
			case \Change\Db\ScalarType::LOB:
			case \Change\Db\ScalarType::DATETIME:
				if (!is_array($fieldDbOptions))
				{
					$fieldDbOptions = is_array($defaultDbOptions) ? $defaultDbOptions : array();
				}
				return $fieldDbOptions;
			default:
				throw new \InvalidArgumentException('Invalid Field type: ' . $scalarType);
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
			throw new \InvalidArgumentException('Invalid Enum values');
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
				$type = 'chardate';
				break;
			case FieldDefinition::TIMESTAMP:
				$type = 'chartimestamp';
				break;
			case FieldDefinition::DECIMAL:
				$type = 'decimal('.$fieldDefinition->getPrecision(). ','. $fieldDefinition->getScale().')';
				break;
			case FieldDefinition::ENUM:
				$type = 'varchar(1000)';
				break;
			case FieldDefinition::FLOAT:
				$type = 'double';
				break;
			case FieldDefinition::INTEGER:
				$type = $fieldDefinition->getAutoNumber() ? 'INTEGER' : 'int(11)';
				break;
			case FieldDefinition::SMALLINT:
				$type = 'tinyint(1)';
				break;
			case FieldDefinition::LOB:
				$type = 'blob';
				break;
			case FieldDefinition::TEXT:
				$type = 'text';
				break;
			default:
				throw new \RuntimeException('Invalid Field Definition type: ' . $fieldDefinition->getType());
				break;
		}

		if ($fieldDefinition->getAutoNumber())
		{
			$type .= ' PRIMARY KEY AUTOINCREMENT';
		}
		elseif (!$fieldDefinition->getNullable())
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
				$type .= ' DEFAULT ' . var_export($fieldDefinition->getDefaultValue(), true);
			}
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
		$sqlParts = array();
		foreach ($tableDefinition->getFields() as $field)
		{
			/* @var $field \Change\Db\Schema\FieldDefinition */
			$oldField = $oldDef->getField($field->getName());
			if (!$oldField)
			{
				$type = $this->generateColumnType($field);
				$sql = 'ALTER TABLE ['.$tableDefinition->getName().'] ADD ['.$field->getName().'] '. $type;
				$sqlParts[] = $sql;
				$this->execute($sql);
			}
		}
		return implode(';'.PHP_EOL, $sqlParts);
	}
	
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @return string SQL definition
	 */
	public function createTable(\Change\Db\Schema\TableDefinition $tableDefinition)
	{
		$sql = 'CREATE TABLE ['.$tableDefinition->getName().'] (';
		$parts = array();
		$indexParts = array();
		foreach ($tableDefinition->getFields() as $field)
		{
			/* @var $field \Change\Db\Schema\FieldDefinition */
			
			$type = $this->generateColumnType($field);
			$parts[] = '['.$field->getName().'] '. $type;
		}

		foreach ($tableDefinition->getKeys() as $key)
		{
			/* @var $key \Change\Db\Schema\KeyDefinition */
			$kf = array();
			$fields = $key->getFields();
			if (count($fields))
			{
				$autoNumber = false;
				foreach ($fields as $keyField)
				{
					/* @var $keyField \Change\Db\Schema\FieldDefinition */
					$kf[] = '['.$keyField->getName().']';
					$autoNumber = $keyField->getAutoNumber();
				}

				if ($key->isPrimary())
				{
					if (!$autoNumber)
					{
						$parts[] = 'PRIMARY KEY (' . implode(', ', $kf) . ')';
					}
				}
				elseif ($key->isUnique())
				{
					$parts[] = 'CONSTRAINT ['.$key->getName().'] UNIQUE (' . implode(', ', $kf) . ')';
				}
				elseif ($key->isIndex())
				{
					$indexParts[] = 'CREATE INDEX ['.$tableDefinition->getName() . '_' . $key->getName().'] ON ['.$tableDefinition->getName().'] (' . implode(', ', $kf) . ')';
				}
			}
		}

		$sql .= implode(', ', $parts)  . ')';
		$this->execute($sql);
		if (is_array($this->tables) && !in_array($tableDefinition->getName(), $this->tables))
		{
			$this->tables[] = $tableDefinition->getName();
		}

		if (count($indexParts))
		{
			foreach ($indexParts as $indexSql)
			{
				$this->execute($indexSql);
				$sql .= ";" . PHP_EOL . $indexSql;
			}
		}

		$startAuto = $tableDefinition->getOption('AUTONUMBER');
		if ($startAuto && $startAuto > 1)
		{
			$seqSql = "DELETE FROM [sqlite_sequence] WHERE [name] = '".$tableDefinition->getName()."'";
			$this->execute($seqSql);
			$sql .= ";" . PHP_EOL . $seqSql;

			$seqSql = "INSERT INTO [sqlite_sequence] ([name], [seq]) VALUES('".$tableDefinition->getName()."', ".$startAuto.")";
			$this->execute($seqSql);
			$sql .= ";" . PHP_EOL . $seqSql;
		}
		return $sql;
	}
	
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @return string SQL definition
	 */
	public function dropTable(\Change\Db\Schema\TableDefinition $tableDefinition)
	{
		$sql = 'DROP TABLE IF EXISTS ['. $tableDefinition->getName() .']';
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
		return new \Change\Db\SQLite\Schema($this);
	}
}