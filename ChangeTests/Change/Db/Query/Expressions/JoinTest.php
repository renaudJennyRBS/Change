<?php

namespace ChangeTests\Change\Db\Query\Expressions;

use Change\Db\Query\Expressions\Join;

class JoinTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$t = new \Change\Db\Query\Expressions\Table('t');
		$i = new Join($t);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Table', $i->getTableExpression());
		$this->assertEquals(Join::CROSS_JOIN, $i->getType());
		$this->assertNull($i->getSpecification());
		
		try
		{
			$i = new Join('t');
			$this->fail('Argument 1 must be an instance of Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
	}
	
	
	public function testType()
	{
		$i = new Join(new \Change\Db\Query\Expressions\Table('t'));
		foreach (array(Join::INNER_JOIN, Join::FULL_OUTER_JOIN, Join::LEFT_OUTER_JOIN, Join::RIGHT_OUTER_JOIN) as $value)
		{
			$i->setType($value);
			$this->assertEquals($value, $i->getType());
		}
		
		try
		{
			$i->setType('INNER_JOIN');
			$this->fail('Argument 1 must be a valid const');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
	}
		
	public function testToSQL92String()
	{
		$i = new Join(new \Change\Db\Query\Expressions\Table('t'));
		$this->assertEquals('NATURAL CROSS JOIN "t"', $i->toSQL92String());
		
		$i->setType(Join::INNER_JOIN);
		$this->assertEquals('NATURAL INNER JOIN "t"', $i->toSQL92String());
		
		$i->setSpecification(new \Change\Db\Query\Expressions\Raw('r'));
		$i->setType(Join::INNER_JOIN);
		$this->assertEquals('INNER JOIN "t" r', $i->toSQL92String());
	}
}
