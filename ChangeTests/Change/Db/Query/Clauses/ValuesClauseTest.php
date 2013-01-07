<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\ValuesClause;

class ValuesClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new ValuesClause();
		$this->assertEquals('VALUES', $i->getName());	
		
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $i->getValuesList());
		$this->assertCount(0, $i->getValuesList());
		
		
		$el = new \Change\Db\Query\Expressions\ExpressionList();
		$i = new ValuesClause($el);
		
		$this->assertEquals($el, $i->getValuesList());
	}
	
	public function testValuesList()
	{
		$i = new ValuesClause();
		$el = new \Change\Db\Query\Expressions\ExpressionList();
		$i->setValuesList($el);
		$this->assertEquals($el, $i->getValuesList());
		$ret = $i->addValue(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertEquals($ret, $i);
		$this->assertCount(1, $i->getValuesList());
		
		try
		{
			$i->setValuesList(null);
			$this->fail('Argument 1 must be a instance of \Change\Db\Query\Expressions\ExpressionList');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testToSQL92String()
	{		
		$i = new ValuesClause();
		try
		{
			$i->toSQL92String();
			$this->fail('ValuesList can not be empty');
		}
		catch (\RuntimeException $e)
		{
			$this->assertTrue(true);
		}
				
		$i->addValue(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertEquals("VALUES (raw)", $i->toSQL92String());
	}
}
