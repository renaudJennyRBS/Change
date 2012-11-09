<?php

namespace ChangeTests\Db\Query;

use Change\Db\Query;

class SelectQueryTest extends \PHPUnit_Framework_TestCase
{
	public function testSelectNoClauses()
	{
		$selectQuery = new Query\SelectQuery();
		$selectQuery->addColumn(new Query\Expressions\Numeric(1));
		$this->assertEquals('SELECT 1', $selectQuery->pseudoQueryString());
		
		$selectQuery = new Query\SelectQuery();
		$selectQuery->addColumn(new Query\Expressions\Func('SIN', array(new Query\Expressions\Numeric(1))));
		$this->assertEquals('SELECT SIN(1)', $selectQuery->pseudoQueryString());
		
		$selectQuery = new Query\SelectQuery();
		$selectQuery->addColumn(new Query\Expressions\Operation(new Query\Operations\BinaryOperation(new Query\Expressions\Numeric(1), new Query\Expressions\Numeric(2), '+'))); //\Func('SIN', array(new Query\Expressions\Numeric(1))));
		$this->assertEquals('SELECT 1 + 2', $selectQuery->pseudoQueryString());
	}
	
	public function testSelect1()
	{
		// SELECT SUM(t1.c2) FROM test1 AS t1 WHERE t1.c1 IS NULL
		$table1 = new Query\Objects\Table('test1', 't1');
		$selectQuery = new Query\SelectQuery();
		$fromClause = new Query\Clauses\FromClause();
		$fromClause->setTable($table1);
		$whereClause = new Query\Clauses\WhereClause();
		$whereClause->setPredicate(new Query\Predicates\IsNull(new Query\Expressions\Column($table1, 'c1')));
		$selectQuery->setFromClause($fromClause);
		$selectQuery->setWhereClause($whereClause);
		$selectQuery->addColumn(new Query\Expressions\Func('SUM', array(new Query\Expressions\Column($table1, 'c2'))));
		$selectQuery->addColumn(new Query\Expressions\Column($table1, 'c3', 'Toto'));
		//echo $selectQuery->pseudoQueryString(), "\n";
	}
	
	/**
	 * 
	 */
	public function testSelect2()
	{
		// SELECT * FROM test1 AS t1 INNER JOIN test2 AS t2 ON t1.c1 = t2.c2 WHERE t1.c2 > 1 AND t2.c1 = 'toto'
		$table1 = new Query\Objects\Table('test1', 't1');
		$table2 = new Query\Objects\Table('test2', 't2');
		$selectQuery = new Query\SelectQuery();
		$fromClause = new Query\Clauses\FromClause();
		$fromClause->setTable($table1);
		$joinPredicate = new Query\Predicates\Eq(new Query\Expressions\Column($table1, 'c1'), new Query\Expressions\Column($table2, 'c2'));
		$joinClause = new Query\Clauses\InnerJoinClause();
		$joinClause->setTable($table2);
		$joinClause->setPredicate($joinPredicate);
		$fromClause->addJoinClause($joinClause);
		$selectQuery->setFromClause($fromClause);
		$whereClause = new Query\Clauses\WhereClause();
		$whereClause->setPredicate(new Query\Predicates\Like(new Query\Expressions\Column($table1, 'c1'), new Query\Expressions\String('tutu'), Query\Predicates\Like::ANYWHERE));
		$selectQuery->setWhereClause($whereClause);
		//echo $selectQuery->pseudoQueryString(), "\n";
	}
}

