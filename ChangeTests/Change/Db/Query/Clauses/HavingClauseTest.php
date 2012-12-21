<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\HavingClause;

class HavingClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new HavingClause();
		$this->assertEquals('HAVING', $i->getName());	
		$this->assertNull($i->getPredicate());
		
		
		$p = new \Change\Db\Query\Predicates\Conjunction();
		$i = new HavingClause($p);
		
		$this->assertEquals($p, $i->getPredicate());
	}
	
	public function testPredicate()
	{
		$i = new HavingClause();
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
		$i = new HavingClause();
		$this->assertEquals("", $i->toSQL92String());
		
		$p = new \Change\Db\Query\Predicates\Conjunction(new \Change\Db\Query\Expressions\Raw('raw'));
		$i->setPredicate($p);
		$this->assertEquals("HAVING (raw)", $i->toSQL92String());
	}
}
