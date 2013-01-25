<?php
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
	 * @param string $sql
	 * @return integer the number of affected rows
	 * @throws Exception on error
	 */
	function execute($sql);
	
	/**
	 * @param string $script
	 * @param boolean $throwOnError
	 * @throws Exception on error
	 */
	function executeBatch($script, $throwOnError = false);
	
	/**
	 * @throws Exception on error
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
	 * @param string $tableName
	 * @return \Change\Db\Schema\TableDefinition
	 */
	public function newTableDefinition($tableName);
	
	/**
	 * @param string $name
	 * @param array $enumValues
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newEnumFieldDefinition($name, array $enumValues);
	
	/**
	 * @param string $name
	 * @param integer $length
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newCharFieldDefinition($name, $length = 255);
	
	/**
	 * @param string $name
	 * @param integer $length
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newVarCharFieldDefinition($name, $length = 255);
	
	/**
	 * @param string $name
	 * @param array $enumValues
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newNumericFieldDefinition($name, $precision = 13, $scale = 4);
	
	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newBooleanFieldDefinition($name);
	
	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newIntegerFieldDefinition($name);
	
	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newFloatFieldDefinition($name);
	
	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */	
	public function newTextFieldDefinition($name);
	
	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newLobFieldDefinition($name);
	
	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newDateFieldDefinition($name);
	
	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function newTimeStampFieldDefinition($name);
			
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
}