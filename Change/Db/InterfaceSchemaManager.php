<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db;

/**
 * @name \Change\Db\InterfaceSchemaManager
 */
interface InterfaceSchemaManager
{
	/**
	 * @return string|NULL
	 */
	function getName();
	
	/**
	 * @return boolean
	 */
	function check();

	/**
	 * @return void
	 */
	public function closeConnection();

	/**
	 * TODO: check provider transaction
	 * @param string $sql
	 * @return integer the number of affected rows
	 * @throws \Exception on error
	 */
	function execute($sql);
	
	/**
	 * @param string $script
	 * @param boolean $throwOnError
	 * @throws \Exception on error
	 */
	function executeBatch($script, $throwOnError = false);
	
	/**
	 * @throws \Exception on error
	 */
	function clearDB();
	
	/**
	 * @return string[]
	 */
	function getTableNames();
	
	/**
	 * @param string $tableName
	 * @return \Change\Db\Schema\TableDefinition|null
	 */
	public function getTableDefinition($tableName);

	/**
	 * @param integer $scalarType
	 * @param array $fieldDbOptions
	 * @param array $defaultDbOptions
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function getFieldDbOptions($scalarType, array $fieldDbOptions = null, array $defaultDbOptions = null);
	
	
	/**
	 * @param string $tableName
	 * @return \Change\Db\Schema\TableDefinition
	 */
	public function newTableDefinition($tableName);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @throws \InvalidArgumentException
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newEnumFieldDefinition($name, array $dbOptions);
	
	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newCharFieldDefinition($name, array $dbOptions = null);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newVarCharFieldDefinition($name, array $dbOptions = null);
	
	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newNumericFieldDefinition($name, array $dbOptions = null);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newBooleanFieldDefinition($name, array $dbOptions = null);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newIntegerFieldDefinition($name, array $dbOptions = null);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newFloatFieldDefinition($name, array $dbOptions = null);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newTextFieldDefinition($name, array $dbOptions = null);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newLobFieldDefinition($name, array $dbOptions = null);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newDateFieldDefinition($name, array $dbOptions = null);

	/**
	 * @param string $name
	 * @param array $dbOptions
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newTimeStampFieldDefinition($name, array $dbOptions = null);
			
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @return string SQL definition
	 */
	public function createOrAlterTable(\Change\Db\Schema\TableDefinition $tableDefinition);
	
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @return string SQL definition
	 */
	public function createTable(\Change\Db\Schema\TableDefinition $tableDefinition);
	
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 * @param \Change\Db\Schema\TableDefinition $oldDef
	 * @return string SQL definition
	 */
	public function alterTable(\Change\Db\Schema\TableDefinition $tableDefinition, \Change\Db\Schema\TableDefinition $oldDef);

	/**
	 * @return \Change\Db\Schema\SchemaDefinition
	 */
	public function getSystemSchema();
}