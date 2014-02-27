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
 * @name \Change\Db\Schema\Generator
 */
class Generator
{
	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;
	
	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;
	
	/**
	 * @param \Change\Workspace $workspace
	 * @param \Change\Db\DbProvider $dbProvider
	 */	
	public function __construct(\Change\Workspace $workspace, \Change\Db\DbProvider $dbProvider)
	{
		$this->workspace = $workspace;
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @api
	 */
	public function generate()
	{
		$this->generateSystemSchema();
		$this->generatePluginsSchema();
	}

	/**
	 * @api
	 */
	public function generateSystemSchema()
	{
		$dbProvider = $this->dbProvider;
		$schemaManager = $dbProvider->getSchemaManager();

		if (!$schemaManager->check())
		{
			throw new \RuntimeException('unable to connect to database: '.$schemaManager->getName(), 40000);
		}

		$dbSchema = $schemaManager->getSystemSchema();
		if ($dbSchema instanceof \Change\Db\Schema\SchemaDefinition)
		{
			$dbSchema->generate();
		}
	}

	/**
	 * @api
	 */
	public function generatePluginsSchema()
	{
		$dbProvider = $this->dbProvider;
		$schemaManager = $dbProvider->getSchemaManager();

		if (!$schemaManager->check())
		{
			throw new \RuntimeException('unable to connect to database: '.$schemaManager->getName(), 40000);
		}

		if (class_exists('Compilation\Change\Documents\Schema'))
		{
			$documentSchema = new \Compilation\Change\Documents\Schema($schemaManager);
			if ($documentSchema instanceof \Change\Db\Schema\SchemaDefinition)
			{
				$documentSchema->generate();
			}
		}
	}
}