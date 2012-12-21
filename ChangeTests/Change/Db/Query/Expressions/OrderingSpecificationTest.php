<?php

namespace ChangeTests\Change\Db\Query\Expressions;

use Change\Db\Query\Expressions\OrderingSpecification;

class OrderingSpecificationTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new OrderingSpecification(new \Change\Db\Query\Expressions\Raw('r'));
		$this->assertEquals(OrderingSpecification::ASC, $i->getOperator());
		
		try
		{
			$i = new OrderingSpecification('t');
			$this->fail('Argument 1 must be an instance of Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
		
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testOperator(OrderingSpecification $i)
	{
		$i->setOperator(OrderingSpecification::DESC);
		$this->assertEquals(OrderingSpecification::DESC, $i->getOperator());
		
		try
		{
			$i->setOperator('a');
			$this->fail('Argument 1 must be a valid const');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
	}
		
	public function testToSQL92String()
	{
		$i = new OrderingSpecification(new \Change\Db\Query\Expressions\Raw('r'));
		$this->assertEquals('r ASC', $i->toSQL92String());
		
		$i->setOperator(OrderingSpecification::DESC);
		$this->assertEquals('r DESC', $i->toSQL92String());
	}
}