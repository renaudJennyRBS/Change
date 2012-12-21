<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\FromClause;

class FromClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new FromClause();
		$this->assertEquals('FROM', $i->getName());	
		$this->assertNull($i->getTableExpression());
		$this->assertCount(0, $i->getJoins());
		
		$el = new \Change\Db\Query\Expressions\Raw('table');
		$i = new FromClause($el);
		
		$this->assertEquals($el, $i->getTableExpression());
	}
	
	public function testJoins()
	{
		$i = new FromClause();
		$jt = new \Change\Db\Query\Expressions\Raw('jointable');
		$j = new \Change\Db\Query\Expressions\Join($jt);
		
		$i->setJoins(array($j));
		$this->assertCount(1, $i->getJoins());
		
		$ret = $i->addJoin($j);
		$this->assertEquals($ret, $i);
		$this->assertCount(2, $i->getJoins());
			
		try
		{
			$i->setJoins(array('joins'));
			$this->fail('Argument 1 item must be a instance of Expressions\Join');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
	}
	
	public function testToSQL92String()
	{		
		$i = new FromClause(new \Change\Db\Query\Expressions\Raw('table'));
		$this->assertEquals("FROM table", $i->toSQL92String());
		
		$jt = new \Change\Db\Query\Expressions\Raw('jointable');
		$j = new \Change\Db\Query\Expressions\Join($jt);
		$i->addJoin($j);
		
		$this->assertEquals("FROM table NATURAL CROSS JOIN jointable", $i->toSQL92String());
	}
}
