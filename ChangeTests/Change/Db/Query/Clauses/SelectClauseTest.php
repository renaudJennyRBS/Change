<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\SelectClause;

class SelectClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new SelectClause();
		$this->assertEquals('SELECT', $i->getName());	
		$this->assertEquals(SelectClause::QUANTIFIER_ALL, $i->getQuantifier());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $i->getSelectList());
		
		$el = new \Change\Db\Query\Expressions\ExpressionList();
		$el->add(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertNotEquals($el, $i->getSelectList());
		
		$i = new SelectClause($el);
		
		$this->assertEquals($el, $i->getSelectList());
	}
	
	public function testQuantifierString()
	{
		$i = new SelectClause();
		$i->setQuantifier(SelectClause::QUANTIFIER_DISTINCT);
		$this->assertEquals(SelectClause::QUANTIFIER_DISTINCT, $i->getQuantifier());
		
		try
		{
			$i->setQuantifier(null);
			$this->fail('Argument 1 must be a valid const');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testSelectList()
	{
		$i = new SelectClause();
		$ret = $i->addSelect(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertEquals($ret, $i);
		
		$el = $i->getSelectList();	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $el);
		$this->assertEquals(1, $el->count());
	}
	
	public function testToSQL92String()
	{		
		$i = new SelectClause();
		$this->assertEquals("SELECT *", $i->toSQL92String());
		
		$i->setQuantifier(SelectClause::QUANTIFIER_DISTINCT);
		$this->assertEquals("SELECT DISTINCT *", $i->toSQL92String());
		
		
		$i->addSelect(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertEquals("SELECT DISTINCT *, raw", $i->toSQL92String());
		
		$i->addSelect(new \Change\Db\Query\Expressions\Raw('raw2'));
		$i->setQuantifier(SelectClause::QUANTIFIER_ALL);
		$this->assertEquals("SELECT *, raw, raw2", $i->toSQL92String());
	}
}
