<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class TableTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Table('table');
		$this->assertEquals('table', $i->getName());
		$this->assertNull($i->getDatabase());

		$i = new \Change\Db\Query\Expressions\Table('table', 'db');
		$this->assertEquals('table', $i->getName());
		$this->assertEquals('db', $i->getDatabase());
		
		
	}
	
	public function testName()
	{

		$i = new \Change\Db\Query\Expressions\Table(null);
		$i->setName('table');
		$this->assertEquals('table', $i->getName());
	}
	
	public function testDatabase()
	{
	
		$i = new \Change\Db\Query\Expressions\Table(null);
		$i->setDatabase('db');
		$this->assertEquals('db', $i->getDatabase());
	}
	
	public function testToSQL92String()
	{
		$i = new \Change\Db\Query\Expressions\Table('table');
		$this->assertEquals('"table"', $i->toSQL92String());
		
		$i->setDatabase('db');
		$this->assertEquals('"db"."table"', $i->toSQL92String());
	}
}
