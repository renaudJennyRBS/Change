<?php
namespace ChangeTests\Change\Db\Query\Predicates;

class UnaryPredicateTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Predicates\UnaryPredicate();
		$this->assertInstanceOf('\Change\Db\Query\Expressions\UnaryOperation', $i);
		$this->assertNull($i->getExpression());
		$this->assertNull($i->getOperator());
		
		$this->assertInstanceOf('\Change\Db\Query\Predicates\InterfacePredicate', $i);
		
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 * @param \Change\Db\Query\Predicates\UnaryPredicate $i
	 */
	public function testToSQL92String(\Change\Db\Query\Predicates\UnaryPredicate $i)
	{		
		$i->setExpression(new \Change\Db\Query\Expressions\Raw('exp'));
		$i->setOperator('NOT');
		$this->assertEquals('NOT exp', $i->toSQL92String());
		
		
		$i->setOperator(\Change\Db\Query\Predicates\UnaryPredicate::ISNULL);
		$this->assertEquals('exp IS NULL', $i->toSQL92String());
		
		$i->setOperator(\Change\Db\Query\Predicates\UnaryPredicate::ISNOTNULL);
		$this->assertEquals('exp IS NOT NULL', $i->toSQL92String());
	}
}
