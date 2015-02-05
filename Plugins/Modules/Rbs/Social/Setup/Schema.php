<?php
namespace Rbs\Social\Setup;

/**
 * @name \Rbs\User\Setup\Schema
 */
class Schema extends \Change\Db\Schema\SchemaDefinition
{
	const SOCIAL_COUNT_TABLE = 'rbs_social_count';

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
			$this->tables[self::SOCIAL_COUNT_TABLE] = $td = $schemaManager->newTableDefinition(self::SOCIAL_COUNT_TABLE);
			$websiteId = $schemaManager->newIntegerFieldDefinition('website_id')->setNullable(false);
			$documentId = $schemaManager->newIntegerFieldDefinition('document_id')->setNullable(false);
			$td->addField($websiteId)->addField($documentId)
				->addField($schemaManager->newIntegerFieldDefinition('count')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newDateFieldDefinition('last_date')->setNullable(false))
				->addField($schemaManager->newTextFieldDefinition('data')->setNullable(false))
				->addKey($this->newPrimaryKey()->setFields([$websiteId, $documentId]));
		}
		return $this->tables;
	}
}
