<?php
namespace Change\Db\Mysql;
		
/**
 * @name \Change\Db\Mysql\Schema
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
			$idDef = $schemaManager->newIntegerFieldDefinition('document_id')->setNullable(false)->setAutoNumber(true);
			$modelDef = $schemaManager->newVarCharFieldDefinition('document_model', 50)->setDefaultValue('')->setNullable(false);
			
			$this->tables['change_document'] = $schemaManager->newTableDefinition('change_document')
				->addField($idDef)->addField($modelDef)
				->addField($schemaManager->newVarCharFieldDefinition('tree_name', 50))
				->addKey($this->newPrimaryKey()->addField($idDef))
				->setOption('AUTONUMBER', 100000);
			
			$idDef = $schemaManager->newIntegerFieldDefinition('document_id')->setDefaultValue('0')->setNullable(false);
			
			$this->tables['change_document_metas'] = $schemaManager->newTableDefinition('change_document_metas')
			->addField($idDef)
			->addField($schemaManager->newVarCharFieldDefinition('metas', 16777215))
			->addField($schemaManager->newTimeStampFieldDefinition('lastupdate'))
			->addKey($this->newPrimaryKey()->addField($idDef));
			
			$this->tables['change_document_deleted'] = $schemaManager->newTableDefinition('change_document_deleted')
			->addField($idDef)->addField($modelDef)
			->addField($schemaManager->newTimeStampFieldDefinition('deletiondate'))
			->addField($schemaManager->newVarCharFieldDefinition('datas', 16777215))
			->addKey($this->newPrimaryKey()->addField($idDef));
		}
		return $this->tables;
	}
}
