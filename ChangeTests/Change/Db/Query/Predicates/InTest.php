<?php
namespace ChangeTests\Change\Db\Query\Predicates;

use Change\Db\Query\Predicates\In;

class InTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstruct()
	{
		$i = new In();
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $i);
		$this->assertNull($i->getLeftHandExpression());
		$this->assertNull($i->getRightHandExpression());
		$this->assertFalse($i->getNot());
		$this->assertEquals('IN', $i->getOperator());	
	}
	
	
	public function testNot()
	{
		$i = new In();
		$i->setNot(true);
		$this->assertTrue($i->getNot());
		$this->assertEquals('NOT IN', $i->getOperator());

		$i->setNot(false);
		$this->assertFalse($i->getNot());
		$this->assertEquals('IN', $i->getOperator());	
	}
	
	public function testCheckCompile()
	{
		$i = new In(new \Change\Db\Query\Expressions\Raw('lhe'), new \Change\Db\Query\Expressions\Raw('rhe'));
		
		try
		{			
			$i->checkCompile();
			$this->fail('Right Hand Expression must be a Subquery or ExpressionList');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Right Hand Expression must', $e->getMessage());
		}
		
		
		try
		{
			$expList = new \Change\Db\Query\Expressions\ExpressionList();
			$i->setRightHandExpression($expList);
			$i->checkCompile();
			$this->fail('Right Hand Expression must be a ExpressionList with one element or more');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringStartsWith('Right Hand Expression must be', $e->getMessage());
		}
		$expList->add(new \Change\Db\Query\Expressions\Raw('rhe1'));
		$i->checkCompile();
	}
	
	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->getApplicationServices()->getDbProvider();
	}
	
	public function testToSQL92String()
	{		

		$expList = new \Change\Db\Query\Expressions\ExpressionList();
		$expList->add(new \Change\Db\Query\Expressions\Raw('rhe1'));
		$i = new In(new \Change\Db\Query\Expressions\Raw('lhe'), $expList);	
		$i->setRightHandExpression($expList);
		$this->assertEquals("lhe IN (rhe1)", $i->toSQL92String());
		
		$s = new \Change\Db\Query\SelectQuery($this->getDbProvider());
		$s->setSelectClause(new \Change\Db\Query\Clauses\SelectClause($expList));
		$i->setRightHandExpression(new \Change\Db\Query\Expressions\SubQuery($s));
		
		$this->assertEquals("lhe IN (SELECT rhe1)", $i->toSQL92String());
	}
}
