<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class UnaryOperationTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\UnaryOperation();
		$this->assertNull($i->getOperator());
		$this->assertNull($i->getExpression());

		$r = new \Change\Db\Query\Expressions\Raw('raw');
		$i = new \Change\Db\Query\Expressions\UnaryOperation($r, 'op');
		$this->assertEquals('op', $i->getOperator());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $i->getExpression());
		
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 * @param \Change\Db\Query\Expressions\UnaryOperation $i
	 */
	public function testExpression(\Change\Db\Query\Expressions\UnaryOperation $i)
	{		
		$i->setExpression(new \Change\Db\Query\Expressions\Raw('test'));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $i->getExpression());
		$this->assertEquals('test', $i->getExpression()->getValue());
		
		try
		{
			$i->setExpression('Expression');
			$this->fail('Argument 1 must be an instance of Change\Db\Query\Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
		return $i;
	}
	
	/**
	 * @depends testExpression
	 * @param \Change\Db\Query\Expressions\UnaryOperation $i
	 */
	public function testToSQL92String(\Change\Db\Query\Expressions\UnaryOperation $i)
	{
		$this->assertEquals('op test', $i->toSQL92String());
		
		$i = new \Change\Db\Query\Expressions\UnaryOperation();
		try
		{
			$i->toSQL92String();
			$this->fail('Expression can not be null');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Expression', $e->getMessage());
		}
	}
}
