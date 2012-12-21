<?php
namespace ChangeTests\Change\Db\Query\Predicates;

class DisjunctionTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Predicates\Disjunction();
		$this->assertInstanceOf('\Change\Db\Query\Predicates\InterfacePredicate', $i);
		
		$i = new \Change\Db\Query\Predicates\Disjunction(new \Change\Db\Query\Expressions\Raw('f1'), new \Change\Db\Query\Expressions\Raw('f2'));
		$this->assertCount(2, $i->getArguments());
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 * @param \Change\Db\Query\Predicates\Disjunction $i
	 */
	public function testArguments(\Change\Db\Query\Predicates\Disjunction $i)
	{	
		$i->setArguments(array());
		$this->assertCount(0, $i->getArguments());
		
		$i->setArguments(array(new \Change\Db\Query\Expressions\Raw('f1')));
		$this->assertCount(1, $i->getArguments());
		
		$ret = $i->addArgument(new \Change\Db\Query\Expressions\Raw('f2'));
		$this->assertEquals($ret, $i);
		$this->assertCount(2, $i->getArguments());
		
		return $i;
	}
	
	/**
	 * @depends testArguments
	 * @param \Change\Db\Query\Predicates\Disjunction $i
	 */
	public function testToSQL92String(\Change\Db\Query\Predicates\Disjunction $i)
	{		
		$this->assertEquals('(f1 OR f2)', $i->toSQL92String());
	}
}
