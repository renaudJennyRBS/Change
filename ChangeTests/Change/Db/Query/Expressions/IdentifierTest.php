<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class IdentifierTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Identifier();
		$this->assertCount(0, $i->getParts());

		$i = new \Change\Db\Query\Expressions\Identifier(array('test', ' tutu'));
		$this->assertEquals(array('test', 'tutu'), $i->getParts());
	}
	
	public function testParts()
	{
		$i = new \Change\Db\Query\Expressions\Identifier();
		$i->setParts(array('test', ' tutu', '', ' 0'));
		$this->assertEquals(array('test', 'tutu', '0'), $i->getParts());
		
		try
		{
			$i = new \Change\Db\Query\Expressions\Identifier('test');
			$this->fail('Argument 1 must be an instance of Array');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
	}
		
	public function testToSQL92String()
	{
		$i = new \Change\Db\Query\Expressions\Identifier(array('a', 'b'));
		$this->assertEquals('"a"."b"', $i->toSQL92String());
	}
}
