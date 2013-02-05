<?php
namespace ChangeTests\Change\Db\Mysql;

use Change\Db\Mysql\DbProvider;
use Change\Db\ScalarType;

class DbProviderTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public function testGetInstance()
	{
		$provider = $this->getApplication()->getApplicationServices()->getDbProvider();
		/* @var $provider \Change\Db\Mysql\DbProvider */
		$this->assertInstanceOf('\Change\Db\Mysql\DbProvider', $provider);
		
		$this->assertEquals('mysql', $provider->getType());
		$this->assertNull($provider->getId());
		
		$pdo = $provider->getDriver();
		try 
		{
			$pdo->exec('INVALID SQL');
			$this->fail('Invalid PDO Exception');
			
		}
		catch (\PDOException $e)
		{
			$this->assertStringStartsWith('SQLSTATE', $e->getMessage());
			$this->assertEquals('42000', $e->getCode());
		}
		
		return $provider;
	}
	
	/**
	 * @depends testGetInstance
	 */
	public function testTransaction(DbProvider $provider)
	{
		$this->assertFalse($provider->inTransaction());
		
		$provider->beginTransaction();
		
		$this->assertTrue($provider->inTransaction());
	
		$provider->commit();
		
		$this->assertFalse($provider->inTransaction());
		
		$provider->beginTransaction();
		
		$this->assertTrue($provider->inTransaction());
		
		$provider->rollBack();
		
		$this->assertFalse($provider->inTransaction());	
		
		return $provider;
	}
	
	/**
	 * @depends testTransaction
	 */
	public function testValues(DbProvider $provider)
	{
		$this->assertSame(1, $provider->phpToDB(true, ScalarType::BOOLEAN));
		$this->assertSame(true, $provider->dbToPhp(1, ScalarType::BOOLEAN));
		$this->assertSame(0, $provider->phpToDB(false, ScalarType::BOOLEAN));
		$this->assertSame(false, $provider->dbToPhp(0, ScalarType::BOOLEAN));
		
		$dt = new \DateTime('now', new \DateTimeZone('UTC'));
		$dbval =  $dt->format('Y-m-d H:i:s');
		
		$this->assertSame($dbval, $provider->phpToDB($dt, ScalarType::DATETIME));
		$this->assertEquals($dt, $provider->dbToPhp($dbval, ScalarType::DATETIME));
		return $provider;
	}

	/**
	 * @depends testValues
	 */
	public function testGetLastInsertId(DbProvider $provider)
	{
		$pdo = $provider->getDriver();
		$pdo->exec('DROP TABLE IF EXISTS `test_auto_number`');
		$pdo->exec('CREATE TABLE `test_auto_number` (`auto` int(11) NOT NULL AUTO_INCREMENT, `test` int(11) NOT NULL, PRIMARY KEY (`auto`)) ENGINE=InnoDB AUTO_INCREMENT=5000');
		$pdo->exec('INSERT INTO `test_auto_number` (`auto`, `test`) VALUES (NULL, \'2\')');
		$auto = $provider->getLastInsertId('test_auto_number');
		$this->assertEquals(5000, $auto);
	}
}
