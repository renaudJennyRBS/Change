<?php
namespace ChangeTests\Change\Db\Mysql;

use Change\Db\Mysql\DbProvider;
use Change\Db\ScalarType;

class DbProviderTest extends \PHPUnit_Framework_TestCase
{

	public function testGetInstance()
	{
		$provider = \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
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
	}
	
}
