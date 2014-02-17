<?php
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
		}
		return $this->tables;
	}
}
