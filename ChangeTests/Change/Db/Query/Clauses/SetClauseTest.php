<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\SetClause;

class SetClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new SetClause();
		$this->assertEquals('SET', $i->getName());	
		
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $i->getSetList());
		$this->assertCount(0, $i->getSetList());
		
		
		$el = new \Change\Db\Query\Expressions\ExpressionList();
		$i = new SetClause($el);
		
		$this->assertEquals($el, $i->getSetList());
	}
	
	public function testSetList()
	{
		$i = new SetClause();
		$el = new \Change\Db\Query\Expressions\ExpressionList();
		$i->setSetList($el);
		$this->assertEquals($el, $i->getSetList());
		$ret = $i->addSet(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertEquals($ret, $i);
		$this->assertCount(1, $i->getSetList());
		
		try
		{
			$i->setSetList(null);
			$this->fail('Argument 1 must be a instance of \Change\Db\Query\Expressions\ExpressionList');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
	public function testCheckCompile()
	{
		$i = new SetClause();
		try
		{
			$i->checkCompile();
			$this->fail('Values can not be empty');
		}
		catch (\RuntimeException $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testToSQL92String()
	{		
		$i = new SetClause();				
		$i->addSet(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertEquals("SET raw", $i->toSQL92String());
	}
}
