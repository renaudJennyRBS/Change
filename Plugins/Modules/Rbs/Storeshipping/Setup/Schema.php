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
	const STORE_STOCK_TABLE = 'rbs_storeshipping_dat_store_stock';

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
				->addField($schemaManager->newVarCharFieldDefinition('coordinates', ['length' => 100])->setNullable(true))
				->addField($schemaManager->newVarCharFieldDefinition('location_address', ['length' => 255])->setNullable(true))
				->addField($schemaManager->newTimeStampFieldDefinition('last_update'))
				->addKey($this->newPrimaryKey()->addField($td->getField('user_id')));

			$this->tables[static::STORE_STOCK_TABLE] = $td = $schemaManager->newTableDefinition(static::STORE_STOCK_TABLE);
			$td->addField($schemaManager->newIntegerFieldDefinition('store_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('sku_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('level')->setNullable(true)->setDefaultValue('0'))
				->addField($schemaManager->newNumericFieldDefinition('price')->setNullable(true))
				->addKey($this->newPrimaryKey()
					->addField($td->getField('sku_id'))
					->addField($td->getField('store_id'))
				);
		}
		return $this->tables;
	}
}
