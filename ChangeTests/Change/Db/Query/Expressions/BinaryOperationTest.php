<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class BinaryOperationTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\BinaryOperation();
		$this->assertNull($i->getLeftHandExpression());
		$this->assertNull($i->getRightHandExpression());
		$this->assertNull($i->getOperator());
	}


	public function testLeftHandExpression()
	{
		$leftHandExpression = new \Change\Db\Query\Expressions\Raw('LeftHandExpression');
		$i = new \Change\Db\Query\Expressions\BinaryOperation($leftHandExpression);
		$this->assertEquals($leftHandExpression, $i->getLeftHandExpression());
		
		$lhe = new \Change\Db\Query\Expressions\Raw('lhe');
		$i->setLeftHandExpression($lhe);
		$this->assertEquals($lhe, $i->getLeftHandExpression());
		
		try 
		{
			$i = new \Change\Db\Query\Expressions\BinaryOperation('test');
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
		$i = new \Change\Db\Query\Expressions\BinaryOperation(null, $rightHandExpression);
		$this->assertEquals($rightHandExpression, $i->getRightHandExpression());
	
		$rhe = new \Change\Db\Query\Expressions\Raw('rhe');
		$i->setRightHandExpression($rhe);
		$this->assertEquals($rhe, $i->getRightHandExpression());
	
		try
		{
			$i = new \Change\Db\Query\Expressions\BinaryOperation(null, 'test');
			$this->fail('Argument 2 must be an instance of Change\Db\Query\Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 2', $e->getMessage());
		}
	}
	
	public function testOperator()
	{
		$i = new \Change\Db\Query\Expressions\BinaryOperation(null, null, 'operator');
		$this->assertEquals('operator', $i->getOperator());
	}
	
	public function testCheckCompile()
	{
		$exp = new \Change\Db\Query\Expressions\Raw('Exp');
		$i = new \Change\Db\Query\Expressions\BinaryOperation();
		try
		{
			$i->checkCompile();
			$this->fail('Invalid Left Hand Expression');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Left Hand Expression', $e->getMessage());
		}
		
		$i->setLeftHandExpression($exp);
		try
		{
			$i->checkCompile();
			$this->fail('Invalid Right Hand Expression');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Invalid Right Hand Expression', $e->getMessage());
		}
	}
	
	public function testToSQL92String()
	{
		$rightHandExpression = new \Change\Db\Query\Expressions\Raw('RightHandExpression');
		$leftHandExpression = new \Change\Db\Query\Expressions\Raw('LeftHandExpression');
		
		$i = new \Change\Db\Query\Expressions\BinaryOperation($leftHandExpression, $rightHandExpression, 'operator');
		$this->assertEquals('LeftHandExpression operator RightHandExpression', $i->toSQL92String());
	}
}
