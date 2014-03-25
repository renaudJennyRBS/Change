<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Setup;

/**
 * @name \Rbs\Commerce\Setup\Schema
 */
class Schema extends \Change\Db\Schema\SchemaDefinition
{
	/**
	 * @var \Change\Db\Schema\TableDefinition[]
	 */
	protected $tables;

	const CART_TABLE = 'rbs_commerce_dat_cart';

	/**
	 * @return \Change\Db\Schema\TableDefinition[]
	 */
	public function getTables()
	{
		if ($this->tables === null)
		{
			$schemaManager = $this->getSchemaManager();
			$this->tables[static::CART_TABLE] = $td = $schemaManager->newTableDefinition(static::CART_TABLE);
			$td->addField($schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newDateFieldDefinition('creation_date')->setNullable(false))
				->addField($schemaManager->newTimeStampFieldDefinition('last_update')->setNullable(false))
				->addField($schemaManager->newVarCharFieldDefinition('identifier', array('length' => 40))->setNullable(true))
				->addField($schemaManager->newVarCharFieldDefinition('email')->setNullable(true))
				->addField($schemaManager->newIntegerFieldDefinition('user_id')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newIntegerFieldDefinition('store_id')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newNumericFieldDefinition('total_amount')->setNullable(true))
				->addField($schemaManager->newNumericFieldDefinition('total_amount_with_taxes')->setNullable(true))
				->addField($schemaManager->newNumericFieldDefinition('payment_amount_with_taxes')->setNullable(true))
				->addField($schemaManager->newVarCharFieldDefinition('currency_code', array('length' => 3))->setNullable(true))
				->addField($schemaManager->newIntegerFieldDefinition('line_count')->setNullable(true)->setDefaultValue('0'))
				->addField($schemaManager->newLobFieldDefinition('cart_data')->setNullable(true))
				->addField($schemaManager->newBooleanFieldDefinition('locked')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newBooleanFieldDefinition('processing')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newIntegerFieldDefinition('owner_id')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newIntegerFieldDefinition('transaction_id')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newIntegerFieldDefinition('order_id')->setNullable(false)->setDefaultValue('0'))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->addKey($this->newUniqueKey()->setName('identifier')->addField($td->getField('identifier')))
				->setOption('AUTONUMBER', 1);
		}
		return $this->tables;
	}
}
