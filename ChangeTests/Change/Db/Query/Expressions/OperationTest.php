<?php

namespace ChangeTests\Change\Db\Query\Expressions;

use Change\Db\Query\Expressions\Operation;

class OperationTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$op = new \Change\Db\Query\Expressions\Operation();
		$this->assertNull($op->getOperation());
		
		$unOp = new \Change\Db\Query\Operations\UnaryOperation(new \Change\Db\Query\Expressions\Numeric(1), '-');
		$op = new \Change\Db\Query\Expressions\Operation($unOp);
		$this->assertEquals($unOp, $op->getOperation());
	}
	
	public function testGetSetOperation()
	{
		$unOp = new \Change\Db\Query\Operations\UnaryOperation(new \Change\Db\Query\Expressions\Numeric(1), '-');
		$op = new \Change\Db\Query\Expressions\Operation();
		$op->setOperation($unOp);
		$this->assertEquals($unOp, $op->getOperation());
	}
	
	public function testPseudoQueryString()
	{
		$unOp = new \Change\Db\Query\Operations\UnaryOperation(new \Change\Db\Query\Expressions\Numeric(1), '-');
		$op = new \Change\Db\Query\Expressions\Operation();
		$op->setOperation($unOp);
		$this->assertEquals('- 1', $op->pseudoQueryString());
	}
}