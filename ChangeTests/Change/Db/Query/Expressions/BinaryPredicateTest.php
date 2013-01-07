<?php
namespace ChangeTests\Change\Db\Query\Predicates;

class AssignmentTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Assignment();
		$this->assertInstanceOf('\Change\Db\Query\Expressions\BinaryOperation', $i);
		$this->assertNull($i->getLeftHandExpression());
		$this->assertNull($i->getRightHandExpression());
		$this->assertEquals($i->getOperator(), '=');
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 * @param \Change\Db\Query\Expressions\Assignment $i
	 */
	public function testToSQL92String(\Change\Db\Query\Expressions\Assignment $i)
	{		
		$i->setLeftHandExpression(new \Change\Db\Query\Expressions\Raw('LeftHandExpression'));
		$i->setRightHandExpression(new \Change\Db\Query\Expressions\Raw('RightHandExpression'));
		$this->assertEquals('LeftHandExpression = RightHandExpression', $i->toSQL92String());
	}
}
