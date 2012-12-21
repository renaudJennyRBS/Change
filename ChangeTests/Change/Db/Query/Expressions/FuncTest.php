<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class FuncTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Func();
		$this->assertNull($i->getFunctionName());
		$this->assertCount(0, $i->getArguments());

		$r = new \Change\Db\Query\Expressions\Raw('r');
		$i = new \Change\Db\Query\Expressions\Func('test', array($r));
		$this->assertEquals('test', $i->getFunctionName());
		$this->assertCount(1, $i->getArguments());
	}
	
	public function testFunctionName()
	{
		$i = new \Change\Db\Query\Expressions\Func();
		$i->setFunctionName('Value');
		$this->assertEquals('Value', $i->getFunctionName());
	}
	
	public function testArguments()
	{
		$i = new \Change\Db\Query\Expressions\Func();
		
		$i->setArguments(array(new \Change\Db\Query\Expressions\Raw('r')));
		$this->assertCount(1, $i->getArguments());
		$i->addArgument(new \Change\Db\Query\Expressions\Raw('s'));
		$this->assertCount(2, $i->getArguments());
		
		try
		{
			$i->setArguments('s');
			$this->fail('Argument 1 must be an instance of Array');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
		
		try
		{
			$i->setArguments(array('s'));
			$this->fail('Argument 1 item must be an instance of Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
		
		try
		{
			$i->addArgument('s');
			$this->fail('Argument 1 must be an instance of Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
	}
		
	public function testToSQL92String()
	{
		$i = new \Change\Db\Query\Expressions\Func();
		$this->assertEquals('()', $i->toSQL92String());
		
		$i->setFunctionName('test');
		$this->assertEquals('test()', $i->toSQL92String());
		
		$i->addArgument(new \Change\Db\Query\Expressions\Raw('r'));
		$this->assertEquals('test(r)', $i->toSQL92String());
		
		$i->addArgument(new \Change\Db\Query\Expressions\Raw('s'));
		$this->assertEquals('test(r, s)', $i->toSQL92String());
	}
}
