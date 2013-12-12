<?php
namespace Rbs\User\Setup;

/**
 * @name \Rbs\User\Setup\Schema
 */
class Schema extends \Change\Db\Schema\SchemaDefinition
{
	const ACCOUNT_REQUEST_TABLE = 'rbs_user_account_request';

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
			$this->tables[self::ACCOUNT_REQUEST_TABLE] = $td = $schemaManager->newTableDefinition(self::ACCOUNT_REQUEST_TABLE);
			$requestId = $schemaManager->newIntegerFieldDefinition('request_id')->setNullable(false)->setAutoNumber(true);
			$td->addField($requestId)
				->addField($schemaManager->newVarCharFieldDefinition('email', array('length' => 255))->setNullable(false))
				->addField($schemaManager->newTextFieldDefinition('config_parameters')->setNullable(false))
				->addField($schemaManager->newDateFieldDefinition('request_date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($requestId))
				->setOption('AUTONUMBER', 1);
		}
		return $this->tables;
	}
}
