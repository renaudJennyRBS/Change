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
			$modelDef = $schemaManager->newVarCharFieldDefinition('document_model', array('length' => 50))->setDefaultValue('')->setNullable(false);
			
			$this->tables['change_document'] = $schemaManager->newTableDefinition('change_document')
				->addField($idDef)->addField($modelDef)
				->addKey($this->newPrimaryKey()->addField($idDef))
				->setOption('AUTONUMBER', 100000);
			
			$idDef = $schemaManager->newIntegerFieldDefinition('document_id')->setDefaultValue('0')->setNullable(false);
			
			$this->tables['change_document_metas'] = $schemaManager->newTableDefinition('change_document_metas')
			->addField($idDef)
			->addField($schemaManager->newTextFieldDefinition('metas'))
			->addField($schemaManager->newTimeStampFieldDefinition('lastupdate'))
			->addKey($this->newPrimaryKey()->addField($idDef));
			
			$this->tables['change_document_deleted'] = $schemaManager->newTableDefinition('change_document_deleted')
			->addField($idDef)->addField($modelDef)
			->addField($schemaManager->newTimeStampFieldDefinition('deletiondate'))
			->addField($schemaManager->newTextFieldDefinition('datas'))
			->addKey($this->newPrimaryKey()->addField($idDef));
			
			$correctionId = $schemaManager->newIntegerFieldDefinition('correction_id')->setNullable(false)->setAutoNumber(true);
			$lcid = $schemaManager->newVarCharFieldDefinition('lcid', array('length' => 10))->setNullable(true);
			$status = $schemaManager->newEnumFieldDefinition('status', array('VALUES' => array('DRAFT', 'VALIDATION', 'PUBLISHABLE', 'FILED')))->setNullable(false)->setDefaultValue('DRAFT');
			$this->tables['change_document_correction'] = $schemaManager->newTableDefinition('change_document_correction')
			->addField($correctionId)
			->addField($idDef)
			->addField($lcid)
			->addField($status)
			->addField($schemaManager->newTimeStampFieldDefinition('creationdate'))
			->addField($schemaManager->newDateFieldDefinition('publicationdate'))
			->addField($schemaManager->newLobFieldDefinition('datas'))
			->addKey($this->newPrimaryKey()->addField($correctionId))
			->addKey($this->newIndexKey()->setName('document')->addField($idDef)->addField($status)->addField($lcid))
			->setOption('AUTONUMBER', 1);
		}
		return $this->tables;
	}
}
