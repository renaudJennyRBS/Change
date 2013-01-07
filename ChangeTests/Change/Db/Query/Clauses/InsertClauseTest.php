<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\InsertClause;

class InsertClauseTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @param string $name
	 * @return \Change\Db\Query\Expressions\Column
	 */
	protected function getNewColumn($name)
	{
		return new \Change\Db\Query\Expressions\Column(new \Change\Db\Query\Expressions\Identifier(array($name)));
	}
	
	
	public function testConstruct()
	{
		$i = new InsertClause();
		$this->assertEquals('INSERT', $i->getName());	
		$this->assertNull($i->getTable());
		
		$table = new \Change\Db\Query\Expressions\Table('test');
		$i = new InsertClause($table);
		
		$this->assertEquals($table, $i->getTable());
	}
	
	
	public function testTable()
	{
		$i = new InsertClause();
	
		$table = new \Change\Db\Query\Expressions\Table('test');
		$i->setTable($table);
	
		$this->assertEquals($table, $i->getTable());
	}
	
	public function testColumns()
	{
		$i = new InsertClause();
		$c = $this->getNewColumn('c1');	
		$ret = $i->addColumn($c);
		$this->assertEquals($i, $ret);
		
		$this->assertCount(1, $i->getColumns());
		$cls = array($this->getNewColumn('c2'), $this->getNewColumn('c3'));
		$i->setColumns($cls);
		
		$this->assertCount(2, $i->getColumns());
	}

	public function testCheckCompile()
	{
		$i = new InsertClause();
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
		$i = new InsertClause(new \Change\Db\Query\Expressions\Table('test'));
		$c = $this->getNewColumn('c1');
		$i->addColumn($c);
		$this->assertEquals("INSERT \"test\" (\"c1\")", $i->toSQL92String());
	}
}
