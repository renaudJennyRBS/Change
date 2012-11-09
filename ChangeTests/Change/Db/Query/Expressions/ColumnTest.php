<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class ColumnTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$table = new \Change\Db\Query\Objects\Table('test1', 't1');
		$column = new \Change\Db\Query\Expressions\Column($table, 'c');
		$this->assertEquals($table, $column->getTable());
		$this->assertEquals('c', $column->getColumnName());
		$this->assertNull($column->getColumnAlias());
		
		$column = new \Change\Db\Query\Expressions\Column($table, 'c', 'd');
		$this->assertEquals($table, $column->getTable());
		$this->assertEquals('c', $column->getColumnName());
		$this->assertEquals('d', $column->getColumnAlias());
	}
	
	public function testSetGetTable()
	{
		$table1 = new \Change\Db\Query\Objects\Table('test1', 't1');
		$column = new \Change\Db\Query\Expressions\Column($table1, 'c');
		$this->assertEquals($table1, $column->getTable());
		$table2 = new \Change\Db\Query\Objects\Table('test2', 't2');
		$column->setTable($table2);
		$this->assertEquals($table2, $column->getTable());
	}
	
	public function testSetGetColumnName()
	{
		$table1 = new \Change\Db\Query\Objects\Table('test1', 't1');
		$column = new \Change\Db\Query\Expressions\Column($table1, 'c', 'd');
		$this->assertEquals('c', $column->getColumnName());
		$column->setColumnName('e');
		$this->assertEquals('e', $column->getColumnName());
	}
	
	public function testSetGetColumnAlias()
	{
		$table1 = new \Change\Db\Query\Objects\Table('test1', 't1');
		$column = new \Change\Db\Query\Expressions\Column($table1, 'c', 'd');
		$this->assertEquals('d', $column->getColumnAlias());
		$column->setColumnAlias('e');
		$this->assertEquals('e', $column->getColumnAlias());
	}
	
	public function testPseudoString()
	{
		$table = new \Change\Db\Query\Objects\Table('test1', 't1');
		$column = new \Change\Db\Query\Expressions\Column($table, 'c');
		$this->assertEquals('t1.c', $column->pseudoQueryString());
		
		$table = new \Change\Db\Query\Objects\Table('test1', 't1');
		$column = new \Change\Db\Query\Expressions\Column($table, 'c', 'd');
		$this->assertEquals('t1.c AS d', $column->pseudoQueryString());
	}
}