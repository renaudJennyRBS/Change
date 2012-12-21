<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class ExpressionListTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\ExpressionList();
		$this->assertCount(0, $i->getList());
		
		$s1 = new \Change\Db\Query\Expressions\String('test');
		$i = new \Change\Db\Query\Expressions\ExpressionList(array($s1));
		
		$this->assertCount(1, $i->getList());
	}
	
	public function testList()
	{
		$s1 = new \Change\Db\Query\Expressions\String('test');	
		$s2 = new \Change\Db\Query\Expressions\Raw('b');
		$i = new \Change\Db\Query\Expressions\ExpressionList();
		$i->setList(array($s1, $s2));
		$this->assertCount(2, $i->getList());
		
		try
		{
			$i->setList($s1);
			$this->fail('Argument 1 must be an instance of Array');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
		
		try
		{
			$i->setList(array($s1, 'test'));
			$this->fail('Argument 1 must be an instance of Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
	}
			
	public function testToSQL92String()
	{
		$s1 = new \Change\Db\Query\Expressions\Raw('a');
		$s2 = new \Change\Db\Query\Expressions\Raw('b');
		$i = new \Change\Db\Query\Expressions\ExpressionList(array($s1, $s2));
		$this->assertEquals('a, b', $i->toSQL92String());
	}
}
