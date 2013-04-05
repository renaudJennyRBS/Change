<?php
namespace ChangeTests\Change\Db\SQLite;

use Change\Db\SQLite\DbProvider;
use Change\Db\SQLite\Schema;

class SchemaTest extends \ChangeTests\Change\TestAssets\TestCase
{
	protected function setUp()
	{
		if (!in_array('sqlite', \PDO::getAvailableDrivers()))
		{
			$this->markTestSkipped('PDO SQLite is not installed.');
		}

		$provider = $this->getApplicationServices()->getDbProvider();
		if (!($provider instanceof DbProvider))
		{
			$this->markTestSkipped('The SQLite DbProvider is not configured.');
		}
		$connectionInfos = $provider->getConnectionInfos();
		if (!isset($connectionInfos['database']))
		{
			$this->markTestSkipped('The SQLite database not defined!');
		}

	}

	public function testGetInstance()
	{
		$provider = $this->getApplicationServices()->getDbProvider();
		$schema = new Schema($provider->getSchemaManager());

		$tables = $schema->getTables();
		$this->assertArrayHasKey('change_document', $tables);
		$this->assertArrayHasKey('change_document_correction', $tables);
		$this->assertArrayHasKey('change_document_deleted', $tables);
		$this->assertArrayHasKey('change_document_metas', $tables);
		$this->assertArrayHasKey('change_path_rule', $tables);

		$schema->generate();
	}
}
