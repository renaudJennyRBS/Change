<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Setup;

/**
 * @name \Rbs\Catalog\Setup\Schema
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
			$this->tables['rbs_catalog_dat_attribute'] = $td = $schemaManager->newTableDefinition('rbs_catalog_dat_attribute');
			$td->addField($schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newIntegerFieldDefinition('attribute_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('product_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('integer_value')->setNullable(true))
				->addField($schemaManager->newFloatFieldDefinition('float_value')->setNullable(true))
				->addField($schemaManager->newDateFieldDefinition('date_value')->setNullable(true))
				->addField($schemaManager->newCharFieldDefinition('string_value')->setNullable(true))
				->addField($schemaManager->newTextFieldDefinition('text_value')->setNullable(true))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->addKey($this->newUniqueKey()->setName('productAttribute')
					->addField($td->getField('attribute_id'))->addField($td->getField('product_id')))
				->setOption('AUTONUMBER', 1);

			$this->tables['rbs_catalog_dat_productlistitem'] = $td = $schemaManager->newTableDefinition('rbs_catalog_dat_productlistitem');
			$td->addField($schemaManager->newIntegerFieldDefinition('listitem_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('store_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('billing_area_id')->setNullable(false))
				->addField($schemaManager->newDateFieldDefinition('sort_date')->setNullable(true))
				->addField($schemaManager->newIntegerFieldDefinition('sort_level')->setNullable(true))
				->addField($schemaManager->newFloatFieldDefinition('sort_price')->setNullable(true))
				->addKey($this->newPrimaryKey()
					->addField($td->getField('listitem_id'))
					->addField($td->getField('store_id'))
					->addField($td->getField('billing_area_id')));
		}
		return $this->tables;
	}
}
