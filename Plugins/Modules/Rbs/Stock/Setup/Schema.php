<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Setup;

/**
 * @name \Rbs\Stock\Setup\Schema
 */
class Schema extends \Change\Db\Schema\SchemaDefinition
{
	const MVT_TABLE = 'rbs_stock_dat_mvt';
	const RES_TABLE = 'rbs_stock_dat_res';
	const AVAILABILITY_TABLE = 'rbs_stock_dat_availability';

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
			$this->tables[self::MVT_TABLE] = $td = $schemaManager->newTableDefinition(self::MVT_TABLE);
			$td->addField($schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newIntegerFieldDefinition('sku_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('movement')->setNullable(true))
				->addField($schemaManager->newIntegerFieldDefinition('warehouse_id')->setNullable(true))
				// Validity of the line
				->addField($schemaManager->newDateFieldDefinition('date')->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('target')->setLength(80)->setNullable(true))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->setOption('AUTONUMBER', 1);

			$this->tables[self::RES_TABLE] = $td = $schemaManager->newTableDefinition(self::RES_TABLE);
			$td->addField($schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newIntegerFieldDefinition('sku_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('reservation')->setNullable(true))
				->addField($schemaManager->newIntegerFieldDefinition('store_id')->setNullable(true))
				->addField($schemaManager->newVarCharFieldDefinition('target')->setLength(80)->setNullable(true))
				->addField($schemaManager->newBooleanFieldDefinition('confirmed')->setNullable(false)->setDefaultValue(0))
				->addField($schemaManager->newDateFieldDefinition('date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->setOption('AUTONUMBER', 1);

			$this->tables[self::AVAILABILITY_TABLE] = $td = $schemaManager->newTableDefinition(self::AVAILABILITY_TABLE);
			$td->addField($schemaManager->newIntegerFieldDefinition('product_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('warehouse_id')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newIntegerFieldDefinition('sku_id')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newIntegerFieldDefinition('availability')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newDateFieldDefinition('date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($td->getField('product_id'))->addField($td->getField('warehouse_id')));
		}
		return $this->tables;
	}
}
