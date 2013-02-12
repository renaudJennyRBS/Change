<?php
namespace ChangeTests\Change\Db\Mysql;

use Change\Db\Mysql\DbProvider;
use Change\Db\Mysql\Schema;

class SchemaTest extends \ChangeTests\Change\TestAssets\TestCase
{
	protected function setUp()
	{
		if (!in_array('mysql', \PDO::getAvailableDrivers()))
		{
			$this->markTestSkipped('PDO Mysql is not installed.');
		}

		$provider = $this->getApplication()->getApplicationServices()->getDbProvider();
		if (!($provider instanceof DbProvider))
		{
			$this->markTestSkipped('The Mysql DbProvider is not configured.');
		}

		$connectionInfos = $provider->getConnectionInfos();
		if (!isset($connectionInfos['database']))
		{
			$this->markTestSkipped('The Mysql database not defined!');
		}
	}

	public function testGetInstance()
	{
		$provider = $this->getApplication()->getApplicationServices()->getDbProvider();
		$schema = new Schema($provider->getSchemaManager());
		
		$this->assertCount(4, $schema->getTables());

		$schema->generate();
	}
}
