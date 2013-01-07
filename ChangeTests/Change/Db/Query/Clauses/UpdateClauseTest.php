<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\UpdateClause;

class UpdateClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new UpdateClause();
		$this->assertEquals('UPDATE', $i->getName());	
		$this->assertNull($i->getTable());
		
		$table = new \Change\Db\Query\Expressions\Table('test');
		$i = new UpdateClause($table);
		
		$this->assertEquals($table, $i->getTable());
	}
	
	
	public function testTable()
	{
		$i = new UpdateClause();
	
		$table = new \Change\Db\Query\Expressions\Table('test');
		$i->setTable($table);
	
		$this->assertEquals($table, $i->getTable());
	}

	public function testCheckCompile()
	{
		$i = new UpdateClause();
		try
		{
			$i->checkCompile();
			$this->fail('Table can not be null');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Table can not be null', $e->getMessage());
		}
	}
	
	public function testToSQL92String()
	{		
		$i = new UpdateClause(new \Change\Db\Query\Expressions\Table('test'));
		$this->assertEquals("UPDATE \"test\"", $i->toSQL92String());
	}
}
