<?php
namespace ChangeTests\Change\Db\Query;

use Change\Db\Query\SQLFragmentBuilder;

class SQLFragmentBuilderTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$fb = new SQLFragmentBuilder();
		$this->assertTrue(true);
	}	
	
	public function testFunc()
	{
		$fb = new SQLFragmentBuilder();
		$func = $fb->func('test', new \Change\Db\Query\Expressions\Raw('raw'));
		
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Func', $func);
		$this->assertEquals('test', $func->getFunctionName());
		
		$args = $func->getArguments();
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $args[0]);
	}

	public function testSum()
	{
		$fb = new SQLFragmentBuilder();
		$func = $fb->sum('test', 'test2');
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Func', $func);
		$this->assertEquals('SUM', $func->getFunctionName());
	
		list($arg1, $arg2) = $func->getArguments();
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $arg1);
		$this->assertEquals('test', $arg1->getValue());
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $arg2);
		$this->assertEquals('test2', $arg2->getValue());
	}
	
	public function testTable()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->table('test', 'db');
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Table', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals('db', $frag->getDatabase());
	}
	
	public function testColumn()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->column('test', 'table');
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Column', $frag);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Identifier', $frag->getColumnName());
		$this->assertEquals(array('test'), $frag->getColumnName()->getParts());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Identifier', $frag->getTableOrIdentifier());
		$this->assertEquals(array('table'), $frag->getTableOrIdentifier()->getParts());
	}
	
	public function testIdentifier()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->identifier('test', 'table');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Identifier', $frag);
		$this->assertEquals(array('test', 'table'), $frag->getParts());
	}
	
	public function testAlias()
	{
		$fb = new SQLFragmentBuilder();
		$ident = $fb->identifier('test', 'table');
		$frag = $fb->alias($ident, 'alias');
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Alias', $frag);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Identifier', $frag->getLeftHandExpression());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Identifier', $frag->getRightHandExpression());
		
		try
		{
			$frag = $fb->alias($ident, null);
			$this->fail('A InvalidArgumentException should be thrown.');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testParameter()
	{
		$fb = new SQLFragmentBuilder();
		
		$frag = $fb->parameter('test');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals(\Change\Db\Query\Expressions\Parameter::STRING, $frag->getType());
		
		
		$frag = $fb->numericParameter('test');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals(\Change\Db\Query\Expressions\Parameter::NUMERIC, $frag->getType());
		
		$frag = $fb->dateTimeparameter('test');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals(\Change\Db\Query\Expressions\Parameter::DATETIME, $frag->getType());
			
		$frag = $fb->lobParameter('test');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals(\Change\Db\Query\Expressions\Parameter::LOB, $frag->getType());
	}
	
	public function testNumber()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->number(5);
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Numeric', $frag);
		$this->assertEquals(5, $frag->getValue());
	}
	
	public function testString()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->string('test');
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\String', $frag);
		$this->assertEquals('test', $frag->getString());
	}
	
	public function testExpressionList()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->expressionList($fb->identifier('test'));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $frag);
		$this->assertCount(1, $frag->getList());
	}
	
	public function testSubQuery()
	{
		$fb = new SQLFragmentBuilder();
		$q  = new \Change\Db\Query\SelectQuery(\Change\Application::getInstance()->getApplicationServices()->getDbProvider());
		$frag = $fb->subQuery($q);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\SubQuery', $frag);
	}
	
	public function testEq()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->eq('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('=', $frag->getOperator());
	}
	
	public function testNeq()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->neq('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('<>', $frag->getOperator());
	}
	
	
	public function testGt()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->gt('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('>', $frag->getOperator());
	}
	
	public function testGte()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->gte('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('>=', $frag->getOperator());
	}
	
	public function testLt()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->lt('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('<', $frag->getOperator());
	}
	
	public function testLte()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->lte('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('<=', $frag->getOperator());
	}
	
	
	public function testLike()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->like('a', 'b', \Change\Db\Query\Predicates\Like::BEGIN, true);
		
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Like', $frag);
		$this->assertEquals('LIKE BINARY', $frag->getOperator());
		$this->assertEquals(\Change\Db\Query\Predicates\Like::BEGIN, $frag->getMatchMode());
	}
	
	public function testIsNull()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->isNull('a');
	
		$this->assertInstanceOf('\Change\Db\Query\Predicates\UnaryPredicate', $frag);
		$this->assertEquals('IS NULL', $frag->getOperator());
	}
	
	public function testIsNotNull()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->isNotNull('a');
	
		$this->assertInstanceOf('\Change\Db\Query\Predicates\UnaryPredicate', $frag);
		$this->assertEquals('IS NOT NULL', $frag->getOperator());
	}
	
	public function testLogicAnd()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->logicAnd('a', 'b');
	
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Conjunction', $frag);
		$this->assertCount(2, $frag->getArguments());
	}
	
	public function testLogicOr()
	{
		$fb = new SQLFragmentBuilder();
		$frag = $fb->logicOr('a', 'b', 'c');
	
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Disjunction', $frag);
		$this->assertCount(3, $frag->getArguments());
	}
}