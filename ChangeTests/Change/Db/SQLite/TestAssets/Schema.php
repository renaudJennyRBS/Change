<?php
namespace ChangeTests\Change\Db\SQLite\TestAssets;
		
/**
 * @name \ChangeTests\Change\Db\SQLite\TestAssets\Schema
 */
class Schema extends \Change\Db\Schema\SchemaDefinition
{
	/**
	 * @var \Change\Db\Schema\TableDefinition[]
	 */
	protected $tables;
		
	/**
	 * @return \Change\Db\Schema\TableDefinition[]
	 */
	public function getTables()
	{
		if ($this->tables === null)
		{
			$schemaManager = $this->getSchemaManager();

			$idDef = $schemaManager->newIntegerFieldDefinition('id')->setDefaultValue('0')->setNullable(false);
			$this->tables['test_t1'] = $schemaManager->newTableDefinition('test_t1')
				->addField($idDef)
				->addField($schemaManager->newVarCharFieldDefinition('f1', array('length' => 50)))
				->addField($schemaManager->newIntegerFieldDefinition('f2'))
				->addField($schemaManager->newNumericFieldDefinition('f3'))
				->addField($schemaManager->newBooleanFieldDefinition('f4'))
				->addField($schemaManager->newVarCharFieldDefinition('f5', array('length' => 50)))
				->addField($schemaManager->newDateFieldDefinition('f6'));

			$this->tables['test_t2'] = $schemaManager->newTableDefinition('test_t2')
				->addField($idDef)
				->addField($schemaManager->newVarCharFieldDefinition('2f1', array('length' => 50)))
				->addField($schemaManager->newIntegerFieldDefinition('2f2'))
				->addField($schemaManager->newNumericFieldDefinition('2f3'))
				->addField($schemaManager->newBooleanFieldDefinition('2f4'))
				->addField($schemaManager->newVarCharFieldDefinition('2f5', array('length' => 50)))
				->addField($schemaManager->newDateFieldDefinition('2f6'));

			$this->tables['test_t3'] = $schemaManager->newTableDefinition('test_t3')
				->addField($idDef)
				->addField($schemaManager->newVarCharFieldDefinition('3f1', array('length' => 50)))
				->addField($schemaManager->newIntegerFieldDefinition('3f2'))
				->addField($schemaManager->newNumericFieldDefinition('3f3'))
				->addField($schemaManager->newBooleanFieldDefinition('3f4'))
				->addField($schemaManager->newVarCharFieldDefinition('3f5', array('length' => 50)))
				->addField($schemaManager->newDateFieldDefinition('2f6'));
		}
		return $this->tables;
	}
}
