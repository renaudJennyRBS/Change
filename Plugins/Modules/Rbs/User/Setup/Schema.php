<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Setup;

/**
 * @name \Rbs\User\Setup\Schema
 */
class Schema extends \Change\Db\Schema\SchemaDefinition
{
	const ACCOUNT_REQUEST_TABLE = 'rbs_user_account_request';
	const RESET_PASSWORD_TABLE = 'rbs_user_reset_password';
	const AUTO_LOGIN_TABLE = 'rbs_user_auto_login';

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

			$this->tables[self::RESET_PASSWORD_TABLE] = $resetPasswordTable = $schemaManager->newTableDefinition(self::RESET_PASSWORD_TABLE);
			$requestId = $schemaManager->newIntegerFieldDefinition('request_id')->setNullable(false)->setAutoNumber(true);
			$resetPasswordTable->addField($requestId)
				->addField($schemaManager->newIntegerFieldDefinition('user_id')->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('token', array('length' => 255))->setNullable(false))
				->addField($schemaManager->newDateFieldDefinition('request_date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($requestId))
				->setOption('AUTONUMBER', 1);

			$this->tables[self::AUTO_LOGIN_TABLE] = $autoLoginTable = $schemaManager->newTableDefinition(self::AUTO_LOGIN_TABLE);
			$id = $schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true);
			$autoLoginTable->addField($id)
				->addField($schemaManager->newIntegerFieldDefinition('user_id')->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('token', array('length' => 255))->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('device', array('length' => 255))->setNullable(false))
				->addField($schemaManager->newDateFieldDefinition('validity_date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($id))
				->setOption('AUTONUMBER', 1);
		}
		return $this->tables;
	}
}
