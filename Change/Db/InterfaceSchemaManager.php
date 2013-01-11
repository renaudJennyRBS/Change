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
	function getTables();
	
	/**
	 * @param string $tableName
	 * @return \Change\Db\Schema\TableDefinition
	 */
	public function getTableDefinition($tableName);

	/**
	 * @param string $treeName
	 * @return string the SQL statements that where executed
	 */
	public function createTreeTable($treeName);
	
	/**
	 * @param string $treeName
	 * @return string the SQL statements that where executed
	 */
	public function dropTreeTable($treeName);
			
	/**
	 * @param string $propertyName
	 * @param string $propertyType
	 * @param string $propertyDbSize
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function getDocumentFieldDefinition($propertyName, $propertyType, $propertyDbSize);
	
	/**
	 * @param \Change\Db\Schema\TableDefinition $tableDefinition
	 */
	public function createOrAlter($tableDefinition);
}