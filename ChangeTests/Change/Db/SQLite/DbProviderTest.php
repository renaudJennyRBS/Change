<?php
namespace ChangeTests\Change\Db\SQLite;

use Change\Db\SQLite\DbProvider;
use Change\Db\ScalarType;

class DbProviderTest extends \ChangeTests\Change\TestAssets\TestCase
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
		/** @var $provider DbProvider */
		$provider = $this->getApplicationServices()->getDbProvider();

		/* @var $provider \Change\Db\SQLite\DbProvider */
		$this->assertInstanceOf('\Change\Db\SQLite\DbProvider', $provider);
		
		$this->assertEquals('sqlite', $provider->getType());
		$this->assertNull($provider->getId());
		
		$pdo = $provider->getDriver();
		try 
		{
			$pdo->exec('INVALID SQL');
			$this->fail('Invalid PDO Exception');
		}
		catch (\PDOException $e)
		{
			$this->assertEquals('HY000', $e->getCode());
		}

		$provider->closeConnection();
	}

	public function testTransaction()
	{
		/** @var $provider DbProvider */
		$provider = $this->getApplicationServices()->getDbProvider();

		$event = new \Zend\EventManager\Event('tm', $this, array('primary' => true));

		$this->assertFalse($provider->inTransaction());
		
		$provider->beginTransaction();
		
		$this->assertTrue($provider->inTransaction());
	
		$provider->commit($event);
		
		$this->assertFalse($provider->inTransaction());
		
		$provider->beginTransaction($event);
		
		$this->assertTrue($provider->inTransaction());
		
		$provider->rollBack($event);
		
		$this->assertFalse($provider->inTransaction());


		$event->setParam('primary', false);
		$provider->beginTransaction($event);
		$this->assertFalse($provider->inTransaction());

		$provider->closeConnection();
	}

	public function testValues()
	{
		/** @var $provider DbProvider */
		$provider = $this->getApplicationServices()->getDbProvider();

		$this->assertSame(1, $provider->phpToDB(true, ScalarType::BOOLEAN));
		$this->assertSame(true, $provider->dbToPhp(1, ScalarType::BOOLEAN));
		$this->assertSame(0, $provider->phpToDB(false, ScalarType::BOOLEAN));
		$this->assertSame(false, $provider->dbToPhp(0, ScalarType::BOOLEAN));
		
		$dt = new \DateTime('now', new \DateTimeZone('UTC'));
		$dbVal =  $dt->format('Y-m-d H:i:s');
		
		$this->assertSame($dbVal, $provider->phpToDB($dt, ScalarType::DATETIME));
		$this->assertEquals($dt, $provider->dbToPhp($dbVal, ScalarType::DATETIME));

		$provider->closeConnection();
	}

	public function testGetLastInsertId()
	{
		/** @var $provider DbProvider */
		$provider = $this->getApplicationServices()->getDbProvider();

		$pdo = $provider->getDriver();
		$pdo->exec("DROP TABLE IF EXISTS [test_auto_number]");
		$pdo->exec("CREATE TABLE [test_auto_number] ([auto] INTEGER PRIMARY KEY AUTOINCREMENT, [test] int(11) NOT NULL)");
		$pdo->beginTransaction();
		$pdo->exec("INSERT INTO [sqlite_sequence] ([name], [seq]) VALUES('test_auto_number', 5000)");
		$pdo->exec("INSERT INTO [test_auto_number] ([test]) VALUES(2)");
		$auto = $provider->getLastInsertId('test_auto_number');
		$this->assertEquals(5001, $auto);
		$pdo->commit();

		$provider->closeConnection();
	}
}
