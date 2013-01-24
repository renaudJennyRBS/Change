<?php
namespace Change\Db\Schema;

/**
 * @name \Change\Db\Schema\SchemaDefinition
 */
class SchemaDefinition
{
	/**
	 * @var \Change\Db\InterfaceSchemaManager
	 */
	protected $schemaManager;
	
	/**
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 */
	public function __construct(\Change\Db\InterfaceSchemaManager $schemaManager)
	{
		$this->setSchemaManager($schemaManager);
	}
	
	/**
	 * @return \Change\Db\InterfaceSchemaManager
	 */
	public function getSchemaManager()
	{
		return $this->schemaManager;
	}
	
	/**
	 * @return \Change\Db\InterfaceSchemaManager
	 */
	public function setSchemaManager(\Change\Db\InterfaceSchemaManager $schemaManager)
	{
		return $this->schemaManager = $schemaManager;
	}	
	
	/**
	 * @api
	 */
	public function generate()
	{
		$schemaManager = $this->getSchemaManager();
		foreach ($this->getTables() as $tableDef)
		{
			/* @var $tableDef \Change\Db\Schema\TableDefinition */
			$schemaManager->createOrAlterTable($tableDef);
		}
	}
	
	/**
	 * @return \Change\Db\Schema\TableDefinition[]
	 */
	public function getTables()
	{
		return array();
	}
	
	/**
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	protected function newPrimaryKey()
	{
		$kd = new KeyDefinition();
		$kd->setType(KeyDefinition::PRIMARY);
		return $kd;
	}
	
	/**
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	protected function newUniqueKey()
	{
		$kd = new KeyDefinition();
		$kd->setType(KeyDefinition::UNIQUE);
		return $kd;
	}
	
	/**
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	protected function newIndexKey()
	{
		$kd = new KeyDefinition();
		$kd->setType(KeyDefinition::INDEX);
		return $kd;
	}
}