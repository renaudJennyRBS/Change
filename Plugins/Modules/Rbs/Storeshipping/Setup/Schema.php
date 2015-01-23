<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Setup;

/**
 * @name \Rbs\Storeshipping\Setup\Schema
 */
class Schema extends \Change\Db\Schema\SchemaDefinition
{
	/**
	 * @var \Change\Db\Schema\TableDefinition[]
	 */
	protected $tables;

	const PROFILE_TABLE = 'rbs_storeshipping_dat_profile';

	/**
	 * @return \Change\Db\Schema\TableDefinition[]
	 */
	public function getTables()
	{
		if ($this->tables === null)
		{
			$schemaManager = $this->getSchemaManager();
			$this->tables[static::PROFILE_TABLE] = $td = $schemaManager->newTableDefinition(static::PROFILE_TABLE);

			$td->addField($schemaManager->newIntegerFieldDefinition('user_id')->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('store_code', ['length' => 50])->setNullable(true))
				->addField($schemaManager->newTimeStampFieldDefinition('last_update'))
				->addKey($this->newPrimaryKey()->addField($td->getField('user_id')));
		}
		return $this->tables;
	}
}
