<?php
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
				->addField($schemaManager->newIntegerFieldDefinition('owner_id')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newIntegerFieldDefinition('store_id')->setNullable(false)->setDefaultValue('0'))
				->addField($schemaManager->newNumericFieldDefinition('price_value')->setNullable(true))
				->addField($schemaManager->newNumericFieldDefinition('price_value_with_tax')->setNullable(true))
				->addField($schemaManager->newVarCharFieldDefinition('currency_code', array('length' => 3))->setNullable(true))
				->addField($schemaManager->newIntegerFieldDefinition('line_count')->setNullable(true)->setDefaultValue('0'))
				->addField($schemaManager->newLobFieldDefinition('cart_data')->setNullable(true))
				->addField($schemaManager->newBooleanFieldDefinition('locked')->setNullable(false)->setDefaultValue('0'))
				->addKey($this->newPrimaryKey()->addField($td->getField('id')))
				->addKey($this->newUniqueKey()->setName('identifier')->addField($td->getField('identifier')))
				->setOption('AUTONUMBER', 1);
		}
		return $this->tables;
	}
}
