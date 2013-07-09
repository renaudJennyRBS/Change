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

			$this->tables['rbs_catalog_category_products'] = $td = $schemaManager->newTableDefinition('rbs_catalog_category_products');
			$td->addField($schemaManager->newIntegerFieldDefinition('category_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('product_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('condition_id')->setNullable(false))
				->addField($schemaManager->newIntegerFieldDefinition('priority')->setNullable(false)->setDefaultValue(1))
				->addKey($this->newPrimaryKey()->addField($td->getField('category_id'))
					->addField($td->getField('product_id'))->addField($td->getField('condition_id')));
		}
		return $this->tables;
	}
}
