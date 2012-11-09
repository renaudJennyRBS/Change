<?php

namespace ChangeTests\Db\Query\Objects;

use Change\Db\Query;

class TableTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$table1 = new Query\Objects\Table('test');
		$this->assertEquals('test', $table1->getName());
		
		$table2 = new Query\Objects\Table('test' , 't1');
		$this->assertEquals('test', $table2->getName());
		$this->assertEquals('t1', $table2->getAlias());
	}
	
	public function testSetTableName()
	{
		$table1 = new Query\Objects\Table('test');
		$table1->setName('toto');
		$this->assertEquals('toto', $table1->getName());
	}
	
	public function testSetAlias()
	{
		$table1 = new Query\Objects\Table('test', 't1');
		$table1->setAlias('t2');
		$this->assertEquals('t2', $table1->getAlias());
		
		$table2 = new Query\Objects\Table('test');
		$table2->setAlias('t3');
		$this->assertEquals('t3', $table2->getAlias());
	}
	
	public function testTable()
	{
		$table1 = new Query\Objects\Table('test');
		$this->assertEquals('"test"', $table1->toSQL92String());
	}
	
	public function testTableWithAlias()
	{
		$table1 = new Query\Objects\Table('test', 't1');
		$this->assertEquals('"test" AS "t1"', $table1->toSQL92String());
	}
	
	public function testCrossJoinImplicitNatural()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table');
		$joinInfo = new Query\Objects\JoinInfo($table2);
		$table1->addJoinInfo($joinInfo);
		$this->assertEquals('"root_table" NATURAL CROSS JOIN "joined_table"', $table1->toSQL92String());
	}
	
	public function testCrossJoinExplicitNatural()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table');
		$joinInfo = new Query\Objects\JoinInfo($table2, Query\Objects\JoinInfo::CROSS_JOIN);
		$table1->addJoinInfo($joinInfo);
		$this->assertEquals('"root_table" NATURAL CROSS JOIN "joined_table"', $table1->toSQL92String());
	}
	
	
	public function testInnerJoinNatural()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table');
		$joinInfo = new Query\Objects\JoinInfo($table2, Query\Objects\JoinInfo::INNER_JOIN);
		$table1->addJoinInfo($joinInfo);
		$this->assertEquals('"root_table" NATURAL INNER JOIN "joined_table"', $table1->toSQL92String());
	}
	
	public function testLeftOuterJoinNatural()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table');
		$joinInfo = new Query\Objects\JoinInfo($table2, Query\Objects\JoinInfo::LEFT_OUTER_JOIN);	
		$table1->addJoinInfo($joinInfo);
		$this->assertEquals('"root_table" NATURAL LEFT OUTER JOIN "joined_table"', $table1->toSQL92String());
	}
	
	public function testRightOuterJoinNatural()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table');
		$joinInfo = new Query\Objects\JoinInfo($table2, Query\Objects\JoinInfo::RIGHT_OUTER_JOIN);
		$table1->addJoinInfo($joinInfo);
		$this->assertEquals('"root_table" NATURAL RIGHT OUTER JOIN "joined_table"', $table1->toSQL92String());
	}
	
	public function testFullOuterJoinNatural()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table');
		$joinInfo = new Query\Objects\JoinInfo($table2, Query\Objects\JoinInfo::FULL_OUTER_JOIN);
		$table1->addJoinInfo($joinInfo);
		$this->assertEquals('"root_table" NATURAL FULL OUTER JOIN "joined_table"', $table1->toSQL92String());
	}
	
	public function testInnerJoinUsing()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table');
		$joinInfo = new Query\Objects\JoinInfo($table2, Query\Objects\JoinInfo::INNER_JOIN);
		$table1->addJoinInfo($joinInfo);
		$colList = new Query\Expressions\ExpressionList(array(new Query\Expressions\Column('col1'), new Query\Expressions\Column('col2')));
		$usingOperator = new Query\Expressions\UnaryOperation($colList, 'USING');
		$joinInfo->setSpecification($usingOperator);
		$this->assertEquals('"root_table" INNER JOIN "joined_table" USING "col1", "col2"', $table1->toSQL92String());
	}
	
	public function testInnerJoinOn()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table');
		$joinInfo = new Query\Objects\JoinInfo($table2, Query\Objects\JoinInfo::INNER_JOIN);
		$table1->addJoinInfo($joinInfo);
		$predicate1 = new Query\Predicates\Eq(new Query\Expressions\Column('col1', $table1), new Query\Expressions\Column('col2', $table2));
		$predicate2 = new Query\Predicates\Eq(new Query\Expressions\Column('col3', $table1), new Query\Expressions\Column('col4', $table2));
		$predicate = new Query\Predicates\Conjunction($predicate1, $predicate2);
		$onOperator = new Query\Expressions\UnaryOperation($predicate, 'ON');
		$joinInfo->setSpecification($onOperator);
		$sqlString = $table1->toSQL92String();
		$this->assertEquals('"root_table" INNER JOIN "joined_table" ON "root_table"."col1" = "joined_table"."col2" AND "root_table"."col3" = "joined_table"."col4"', $sqlString);
	}
	
	public function testChainedJoins()
	{
		$table1 = new Query\Objects\Table('root_table');
		$table2 = new Query\Objects\Table('joined_table1');
		$table3 = new Query\Objects\Table('joined_table2');
		$table4 = new Query\Objects\Table('joined_table3');
		
		$joinInfo1 = new Query\Objects\JoinInfo($table2, Query\Objects\JoinInfo::INNER_JOIN);
		$joinInfo2 = new Query\Objects\JoinInfo($table3, Query\Objects\JoinInfo::RIGHT_OUTER_JOIN);
		$joinInfo3 = new Query\Objects\JoinInfo($table4, Query\Objects\JoinInfo::LEFT_OUTER_JOIN);
		$table1->setJoinInfos(array($joinInfo1, $joinInfo2, $joinInfo3));
		
		$colList = new Query\Expressions\ExpressionList(array(new Query\Expressions\Column('col1'), new Query\Expressions\Column('col2')));
		$usingOperator = new Query\Expressions\UnaryOperation($colList, 'USING');
		$joinInfo1->setSpecification($usingOperator);

		$predicate1 = new Query\Predicates\Eq(new Query\Expressions\Column('col1', $table1), new Query\Expressions\Column('col2', $table2));
		$predicate2 = new Query\Predicates\Eq(new Query\Expressions\Column('col3', $table1), new Query\Expressions\Column('col4', $table2));
		$predicate = new Query\Predicates\Conjunction($predicate1, $predicate2);
		$onOperator = new Query\Expressions\UnaryOperation($predicate, 'ON');
		$joinInfo2->setSpecification($onOperator);
		$sqlString = $table1->toSQL92String();
		$this->assertEquals('"root_table" INNER JOIN "joined_table1" USING "col1", "col2" RIGHT OUTER JOIN "joined_table2" ON "root_table"."col1" = "joined_table1"."col2" AND "root_table"."col3" = "joined_table1"."col4" NATURAL LEFT OUTER JOIN "joined_table3"', $sqlString);
	}
}

