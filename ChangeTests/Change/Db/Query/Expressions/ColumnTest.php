<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class ColumnTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$ident = new \Change\Db\Query\Expressions\Identifier(array('a'));
		$i = new \Change\Db\Query\Expressions\Column($ident);
		$this->assertEquals($ident, $i->getColumnName());
		$this->assertNull($i->getTableOrIdentifier());

		$ident2 = new \Change\Db\Query\Expressions\Identifier(array('b'));
		$i = new \Change\Db\Query\Expressions\Column($ident, $ident2);
		$this->assertEquals($ident2, $i->getTableOrIdentifier());
	}
	
	public function testColumnName()
	{
		$ident = new \Change\Db\Query\Expressions\Identifier(array('a'));
		$i = new \Change\Db\Query\Expressions\Column($ident);
		$ident2 = new \Change\Db\Query\Expressions\Identifier(array('b'));
		
		$i->setColumnName($ident2);
		$this->assertEquals($ident2, $i->getColumnName());
		
		try
		{
			$i->setColumnName(null);
			$this->fail('Argument 1 must be an instance of \Change\Db\Query\Expressions\Identifier');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testTableOrIdentifier()
	{
		$ident = new \Change\Db\Query\Expressions\Identifier(array('a'));
		
		$i = new \Change\Db\Query\Expressions\Column($ident);
		$ident2 = new \Change\Db\Query\Expressions\Identifier(array('b'));
	
		$i->setTableOrIdentifier($ident2);
		$this->assertEquals($ident2, $i->getTableOrIdentifier());
	
		try
		{
			$i->setTableOrIdentifier('table');
			$this->fail('Argument 1 must be an instance of Expressions\Table | Expressions\Identifier');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
		
	public function testToSQL92String()
	{
		$ident = new \Change\Db\Query\Expressions\Identifier(array('a'));
		$i = new \Change\Db\Query\Expressions\Column($ident);
		$this->assertEquals('"a"', $i->toSQL92String());
		
		$ident2 = new \Change\Db\Query\Expressions\Identifier(array('b'));
		$i->setTableOrIdentifier($ident2);
		$this->assertEquals('"b"."a"', $i->toSQL92String());
	}
}
