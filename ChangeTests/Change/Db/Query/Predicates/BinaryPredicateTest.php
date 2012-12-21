<?php
namespace ChangeTests\Change\Db\Query\Predicates;

class BinaryPredicateTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Predicates\BinaryPredicate();
		$this->assertInstanceOf('\Change\Db\Query\Expressions\BinaryOperation', $i);
		$this->assertNull($i->getLeftHandExpression());
		$this->assertNull($i->getRightHandExpression());
		$this->assertNull($i->getOperator());
		
		$this->assertInstanceOf('\Change\Db\Query\Predicates\InterfacePredicate', $i);
		
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 * @param \Change\Db\Query\Predicates\BinaryPredicate $i
	 */
	public function testToSQL92String(\Change\Db\Query\Predicates\BinaryPredicate $i)
	{		
		$i->setLeftHandExpression(new \Change\Db\Query\Expressions\Raw('LeftHandExpression'));
		$i->setRightHandExpression(new \Change\Db\Query\Expressions\Raw('RightHandExpression'));
		$i->setOperator('operator');
		$this->assertEquals('LeftHandExpression operator RightHandExpression', $i->toSQL92String());
	}
}
