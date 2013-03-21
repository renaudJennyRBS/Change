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

			$tokenId = $schemaManager->newIntegerFieldDefinition('token_id')->setNullable(false)->setAutoNumber(true);
			$token = $schemaManager->newVarCharFieldDefinition('token', array('length' => 64))->setNullable(false);
			$this->tables['change_oauth'] = $schemaManager->newTableDefinition('change_oauth')
				->addField($tokenId)
				->addField($token)
				->addField($schemaManager->newVarCharFieldDefinition('token_secret', array('length' => 64))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('consumer_key', array('length' => 64))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('consumer_secret', array('length' => 64))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('realm', array('length' => 128))->setNullable(false))
				->addField($schemaManager->newEnumFieldDefinition('token_type', array('VALUES' => array('request', 'access')))->setNullable(false)->setDefaultValue('request'))
				->addField($schemaManager->newTimeStampFieldDefinition('creation_date'))
				->addField($schemaManager->newDateFieldDefinition('validity_date'))
				->addField($schemaManager->newVarCharFieldDefinition('callback', array('length' => 255))->setNullable(false)->setDefaultValue('oob'))
				->addField($schemaManager->newVarCharFieldDefinition('verifier', array('length' => 20))->setNullable(true))
				->addField($schemaManager->newBooleanFieldDefinition('authorized')->setNullable(false)->setDefaultValue(0))
				->addField($schemaManager->newIntegerFieldDefinition('accessor_id'))
				->addKey($this->newPrimaryKey()->addField($tokenId))
				->addKey($this->newUniqueKey()->setName('token')->addField($token))
				->setOption('AUTONUMBER', 1);
		}
		return $this->tables;
	}
}
