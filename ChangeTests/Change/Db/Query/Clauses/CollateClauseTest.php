<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\CollateClause;

class CollateClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new CollateClause();
		$this->assertEquals('COLLATE', $i->getName());	
		$this->assertNull($i->getExpression());
		
		
		$e = new \Change\Db\Query\Expressions\Raw('raw');
		$i = new CollateClause($e);
		
		$this->assertEquals($e, $i->getExpression());
	}
	
	public function testExpression()
	{
		$i = new CollateClause();
		$e = new \Change\Db\Query\Expressions\Raw('raw');
		$i->setExpression($e);
		$this->assertEquals($e, $i->getExpression());
		
		try
		{
			$i->setExpression(null);
			$this->fail('Argument 1 must be a instance of \Change\Db\Query\Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testToSQL92String()
	{		
		$i = new CollateClause();
		try
		{
			$i->toSQL92String();
			$this->fail('Expression can not be null');
		}
		catch (\RuntimeException $e)
		{
			$this->assertTrue(true);
		}
				
		$e = new \Change\Db\Query\Expressions\Raw('raw');
		$i->setExpression($e);
		$this->assertEquals("COLLATE raw", $i->toSQL92String());
	}
}
