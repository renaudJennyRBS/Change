<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\WhereClause;

class WhereClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new WhereClause();
		$this->assertEquals('WHERE', $i->getName());	
		$this->assertNull($i->getPredicate());
		
		
		$p = new \Change\Db\Query\Predicates\Conjunction();
		$i = new WhereClause($p);
		
		$this->assertEquals($p, $i->getPredicate());
	}
	
	public function testPredicate()
	{
		$i = new WhereClause();
		$p = new \Change\Db\Query\Predicates\Conjunction();
		$i->setPredicate($p);
		$this->assertEquals($p, $i->getPredicate());
		
		try
		{
			$i->setPredicate(null);
			$this->fail('Argument 1 must be a instance of \Change\Db\Query\Predicates\InterfacePredicate');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testToSQL92String()
	{		
		$i = new WhereClause();
		$this->assertEquals("", $i->toSQL92String());
		
		$p = new \Change\Db\Query\Predicates\Conjunction(new \Change\Db\Query\Expressions\Raw('raw'));
		$i->setPredicate($p);
		$this->assertEquals("WHERE (raw)", $i->toSQL92String());
	}
}
