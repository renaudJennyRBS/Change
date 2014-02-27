<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Schema;

/**
 * @name \Change\Db\Schema\SchemaDefinition
 * @api
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
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
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