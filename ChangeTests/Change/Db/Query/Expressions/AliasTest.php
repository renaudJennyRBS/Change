<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class AliasTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Alias();
		$this->assertNull($i->getLeftHandExpression());
		$this->assertNull($i->getRightHandExpression());
		$this->assertEquals('AS', $i->getOperator());
	}


	public function testLeftHandExpression()
	{
		$leftHandExpression = new \Change\Db\Query\Expressions\Raw('LeftHandExpression');
		$i = new \Change\Db\Query\Expressions\Alias($leftHandExpression);
		$this->assertEquals($leftHandExpression, $i->getLeftHandExpression());
		
		$lhe = new \Change\Db\Query\Expressions\Raw('lhe');
		$i->setLeftHandExpression($lhe);
		$this->assertEquals($lhe, $i->getLeftHandExpression());
		
		try 
		{
			$i = new \Change\Db\Query\Expressions\Alias('test');
			$this->fail('Argument 1 must be an instance of Change\Db\Query\Expressions\AbstractExpression');
		} 
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
	}
	
	public function testRightHandExpression()
	{
		$rightHandExpression = new \Change\Db\Query\Expressions\Raw('RightHandExpression');
		$i = new \Change\Db\Query\Expressions\Alias(null, $rightHandExpression);
		$this->assertEquals($rightHandExpression, $i->getRightHandExpression());
	
		$rhe = new \Change\Db\Query\Expressions\Raw('rhe');
		$i->setRightHandExpression($rhe);
		$this->assertEquals($rhe, $i->getRightHandExpression());
	
		try
		{
			$i = new \Change\Db\Query\Expressions\Alias(null, 'test');
			$this->fail('Argument 2 must be an instance of Change\Db\Query\Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 2', $e->getMessage());
		}
	}
	
	public function testToSQL92String()
	{
		$rightHandExpression = new \Change\Db\Query\Expressions\Raw('RightHandExpression');
		$leftHandExpression = new \Change\Db\Query\Expressions\Raw('LeftHandExpression');
		
		$i = new \Change\Db\Query\Expressions\Alias($leftHandExpression, $rightHandExpression);
		$this->assertEquals('LeftHandExpression AS RightHandExpression', $i->toSQL92String());
	}
}
