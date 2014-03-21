<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Setup;

/**
 * @name \Rbs\Order\Setup\Schema
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
			$this->tables['rbs_order_seq_order'] = $td = $schemaManager->newTableDefinition('rbs_order_seq_order');
			$td->addField($schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newTimeStampFieldDefinition('creation_date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->setOption('AUTONUMBER', 1);

			$this->tables['rbs_order_seq_invoice'] = $td = $schemaManager->newTableDefinition('rbs_order_seq_invoice');
			$td->addField($schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newTimeStampFieldDefinition('creation_date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->setOption('AUTONUMBER', 1);

			$this->tables['rbs_order_seq_shipment'] = $td = $schemaManager->newTableDefinition('rbs_order_seq_shipment');
			$td->addField($schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newTimeStampFieldDefinition('creation_date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->setOption('AUTONUMBER', 1);

			$this->tables['rbs_order_seq_creditnote'] = $td = $schemaManager->newTableDefinition('rbs_order_seq_creditnote');
			$td->addField($schemaManager->newIntegerFieldDefinition('id')->setNullable(false)->setAutoNumber(true))
				->addField($schemaManager->newTimeStampFieldDefinition('creation_date')->setNullable(false))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->setOption('AUTONUMBER', 1);
		}
		return $this->tables;
	}
}
