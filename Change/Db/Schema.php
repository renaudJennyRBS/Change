<?php
namespace Change\Db;

/**
 * @name \Change\Db\Schema
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

			$this->tables['change_path_rule'] = $td = $schemaManager->newTableDefinition('change_path_rule');
			$td->addField($schemaManager->newIntegerFieldDefinition('rule_id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newIntegerFieldDefinition('website_id')->setNullable(false)->setDefaultValue(0))
				->addField($schemaManager->newVarCharFieldDefinition('lcid', array('length' => 10))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('path', array('length' => 255))->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('document_id')->setNullable(true))
				->addField($schemaManager->newIntegerFieldDefinition('section_id')->setNullable(true))
				->addField($schemaManager->newIntegerFieldDefinition('http_status')->setNullable(false)->setDefaultValue(200))
				->addField($schemaManager->newTextFieldDefinition('config_datas'))
				->addKey($this->newPrimaryKey()
					->addField($td->getField('rule_id')))
				->addKey($this->newUniqueKey()->setName('path')
					->addField($td->getField('website_id'))
					->addField($td->getField('lcid'))
					->addField($td->getField('path')))
				->setOption('AUTONUMBER', 1);

			$this->tables['change_plugin'] = $td = $schemaManager->newTableDefinition('change_plugin');
			$td->addField($schemaManager->newVarCharFieldDefinition('type', array('length' => 25))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('vendor', array('length' => 25))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('name', array('length' => 25))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('package', array('length' => 25))->setNullable(true))
				->addField($schemaManager->newTimeStampFieldDefinition('registration_date')->setNullable(false))
				->addField($schemaManager->newBooleanFieldDefinition('configured')->setNullable(false)->setDefaultValue(0))
				->addField($schemaManager->newBooleanFieldDefinition('activated')->setNullable(false)->setDefaultValue(0))
				->addField($schemaManager->newTextFieldDefinition('config_datas'))
				->addKey($this->newPrimaryKey()
					->addField($td->getField('type'))
					->addField($td->getField('vendor'))
					->addField($td->getField('name')));

			$this->tables['change_storage'] = $td = $schemaManager->newTableDefinition('change_storage');
			$td->addField($schemaManager->newIntegerFieldDefinition('item_id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newVarCharFieldDefinition('store_name', array('length' => 50))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('store_path', array('length' => 255))->setNullable(false))
				->addField($schemaManager->newTextFieldDefinition('infos'))
				->addKey($this->newPrimaryKey()
					->addField($td->getField('item_id')))
				->addKey($this->newUniqueKey()->setName('path')
					->addField($td->getField('store_name'))
					->addField($td->getField('store_path')))
				->setOption('AUTONUMBER', 1);

		}
		return $this->tables;
	}
}
