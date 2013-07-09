<?php
namespace ChangeTests\Change\Db\Query;

use Change\Db\Query\SQLFragmentBuilder;

class SQLFragmentBuilderTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	protected function getNewSQLFragmentBuilder()
	{
		return new SQLFragmentBuilder(new \Change\Db\SqlMapping());
	}
	
	public function testConstruct()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$this->assertTrue(true);
	}	
	
	public function testFunc()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$func = $fb->func('test', new \Change\Db\Query\Expressions\Raw('raw'));
		
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Func', $func);
		$this->assertEquals('test', $func->getFunctionName());
		
		$args = $func->getArguments();
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $args[0]);
	}

	public function testSum()
	{
		$fb = $this->getNewSQLFragmentBuilder();
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
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->table('test', 'db');
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Table', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals('db', $frag->getDatabase());
	}
	
	public function testColumn()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->column('test', 'table');
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Column', $frag);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Identifier', $frag->getColumnName());
		$this->assertEquals(array('test'), $frag->getColumnName()->getParts());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Identifier', $frag->getTableOrIdentifier());
		$this->assertEquals(array('table'), $frag->getTableOrIdentifier()->getParts());
	}
	
	public function testIdentifier()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->identifier('test', 'table');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Identifier', $frag);
		$this->assertEquals(array('test', 'table'), $frag->getParts());
	}
	
	public function testAlias()
	{
		$fb = $this->getNewSQLFragmentBuilder();
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
		$fb = $this->getNewSQLFragmentBuilder();
		
		$frag = $fb->parameter('test');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals(\Change\Db\ScalarType::STRING, $frag->getType());
		
		
		$frag = $fb->integerParameter('test');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals(\Change\Db\ScalarType::INTEGER, $frag->getType());
		
		$frag = $fb->dateTimeParameter('test');
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals(\Change\Db\ScalarType::DATETIME, $frag->getType());
			
		
		$frag = $fb->typedParameter('test', \Change\Db\ScalarType::LOB);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Parameter', $frag);
		$this->assertEquals('test', $frag->getName());
		$this->assertEquals(\Change\Db\ScalarType::LOB, $frag->getType());
	}
	
	public function testNumber()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->number(5);
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Numeric', $frag);
		$this->assertEquals(5, $frag->getValue());
	}
	
	public function testString()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->string('test');
	
		$this->assertInstanceOf('\Change\Db\Query\Expressions\String', $frag);
		$this->assertEquals('test', $frag->getValue());
	}
	
	public function testExpressionList()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->expressionList($fb->identifier('test'));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $frag);
		$this->assertCount(1, $frag->getList());
	}
	
	public function testSubQuery()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$q  = new \Change\Db\Query\SelectQuery($this->getApplicationServices()->getDbProvider());
		$frag = $fb->subQuery($q);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\SubQuery', $frag);
	}
	
	public function testEq()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->eq('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('=', $frag->getOperator());
	}
	
	public function testNeq()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->neq('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('<>', $frag->getOperator());
	}
	
	
	public function testGt()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->gt('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('>', $frag->getOperator());
	}
	
	public function testGte()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->gte('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('>=', $frag->getOperator());
	}
	
	public function testLt()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->lt('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('<', $frag->getOperator());
	}
	
	public function testLte()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->lte('a', 'b');
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $frag);
		$this->assertEquals('<=', $frag->getOperator());
	}
	
	
	public function testLike()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->like('a', 'b', \Change\Db\Query\Predicates\Like::BEGIN, true);
		
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Like', $frag);
		$this->assertEquals('LIKE BINARY', $frag->getOperator());
		$this->assertEquals(\Change\Db\Query\Predicates\Like::BEGIN, $frag->getMatchMode());
	}
	
	public function testIsNull()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->isNull('a');
	
		$this->assertInstanceOf('\Change\Db\Query\Predicates\UnaryPredicate', $frag);
		$this->assertEquals('IS NULL', $frag->getOperator());
	}
	
	public function testIsNotNull()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->isNotNull('a');
	
		$this->assertInstanceOf('\Change\Db\Query\Predicates\UnaryPredicate', $frag);
		$this->assertEquals('IS NOT NULL', $frag->getOperator());
	}
	
	public function testLogicAnd()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->logicAnd('a', 'b');
	
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Conjunction', $frag);
		$this->assertCount(2, $frag->getArguments());
	}
	
	public function testLogicOr()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->logicOr('a', 'b', 'c');
	
		$this->assertInstanceOf('\Change\Db\Query\Predicates\Disjunction', $frag);
		$this->assertCount(3, $frag->getArguments());
	}
	
	public function testIn()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->in('a', 'b', 'c');
		$this->assertFalse($frag->getNot());
		$this->assertInstanceOf('\Change\Db\Query\Predicates\In', $frag);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $frag->getRightHandExpression());	
		$this->assertCount(2, $frag->getRightHandExpression()->getList());
		
		$q  = new \Change\Db\Query\SelectQuery($this->getApplicationServices()->getDbProvider());
		$frag = $fb->in('a', $fb->subQuery($q));
		$this->assertInstanceOf('\Change\Db\Query\Predicates\In', $frag);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Subquery', $frag->getRightHandExpression());
	}
	
	public function testNotIn()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->notIn('a', 'b');
		$this->assertTrue($frag->getNot());
		$this->assertInstanceOf('\Change\Db\Query\Predicates\In', $frag);
		$this->assertInstanceOf('\Change\Db\Query\Expressions\ExpressionList', $frag->getRightHandExpression());
		$this->assertCount(1, $frag->getRightHandExpression()->getList());
	}
	
	public function testAddition()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->addition('a', 'b');
		$this->assertEquals('+', $frag->getOperator());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $frag->getLeftHandExpression());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $frag->getRightHandExpression());
	}
	
	public function testSubtraction()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->subtraction('a', 'b');
		$this->assertEquals('-', $frag->getOperator());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $frag->getLeftHandExpression());
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $frag->getRightHandExpression());
	}

	public function testConcat()
	{
		$fb = $this->getNewSQLFragmentBuilder();
		$frag = $fb->concat('a', $fb->column('b'));
		$this->assertEquals('a || "b"', $frag->toSQL92String());
	}
}