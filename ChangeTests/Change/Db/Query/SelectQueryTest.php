<?php

namespace ChangeTests\Change\Db\Query;

use Change\Db\Query\SelectQuery;

class SelectQueryTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->getApplicationServices()->getDbProvider();
	}
	
	/**
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	protected function getSQLFragmentBuilder()
	{
		return new \Change\Db\Query\SQLFragmentBuilder($this->getDbProvider()->getSqlMapping());
	}
	
	public function testConstruct()
	{
		$selectQuery = new SelectQuery($this->getDbProvider());
		return $selectQuery;
	}
	
	/**
	 * @depends testConstruct
	 * @param SelectQuery $selectQuery
	 */	
	public function testSelectClause($selectQuery)
	{
		$this->assertNull($selectQuery->getSelectClause());
		$cl = new \Change\Db\Query\Clauses\SelectClause($this->getSQLFragmentBuilder()->expressionList('c1', 'c2'));
		$selectQuery->setSelectClause($cl);
		
		$this->assertEquals($cl, $selectQuery->getSelectClause());
		return $selectQuery;
	}	
	
	/**
	 * @depends testSelectClause
	 * @param SelectQuery $selectQuery
	 */
	public function testFromClause($selectQuery)
	{
		$this->assertNull($selectQuery->getFromClause());
		$cl = new \Change\Db\Query\Clauses\FromClause($this->getSQLFragmentBuilder()->table('table'));
		$selectQuery->setFromClause($cl);
	
		$this->assertEquals($cl, $selectQuery->getFromClause());
		return $selectQuery;
	}
	
	/**
	 * @depends testFromClause
	 * @param SelectQuery $selectQuery
	 */
	public function testWhereClause($selectQuery)
	{
		$this->assertNull($selectQuery->getWhereClause());
		$cl = new \Change\Db\Query\Clauses\WhereClause($this->getSQLFragmentBuilder()->logicAnd('w'));
		$selectQuery->setWhereClause($cl);
	
		$this->assertEquals($cl, $selectQuery->getWhereClause());
		return $selectQuery;
	}
	
	/**
	 * @depends testWhereClause
	 * @param SelectQuery $selectQuery
	 */
	public function testGroupByClause($selectQuery)
	{
		$this->assertNull($selectQuery->getGroupByClause());
		$cl = new \Change\Db\Query\Clauses\GroupByClause($this->getSQLFragmentBuilder()->expressionList('g1', 'g2'));
		$selectQuery->setGroupByClause($cl);
	
		$this->assertEquals($cl, $selectQuery->getGroupByClause());
		return $selectQuery;
	}
	
	/**
	 * @depends testGroupByClause
	 * @param SelectQuery $selectQuery
	 */
	public function testHavingClause($selectQuery)
	{
		$this->assertNull($selectQuery->getHavingClause());
		$cl = new \Change\Db\Query\Clauses\HavingClause($this->getSQLFragmentBuilder()->logicOr('h'));
		$selectQuery->setHavingClause($cl);
	
		$this->assertEquals($cl, $selectQuery->getHavingClause());
		return $selectQuery;
	}
	
	/**
	 * @depends testHavingClause
	 * @param SelectQuery $selectQuery
	 */
	public function testOrderByClause($selectQuery)
	{
		$this->assertNull($selectQuery->getOrderByClause());
		$cl = new \Change\Db\Query\Clauses\OrderByClause($this->getSQLFragmentBuilder()->expressionList('o1'));
		$selectQuery->setOrderByClause($cl);
	
		$this->assertEquals($cl, $selectQuery->getOrderByClause());
		return $selectQuery;
	}
	
	/**
	 * @depends testOrderByClause
	 * @param SelectQuery $selectQuery
	 */
	public function testCollateClause($selectQuery)
	{
		$this->assertNull($selectQuery->getCollateClause());
		$c = new \Change\Db\Query\Clauses\CollateClause(new \Change\Db\Query\Expressions\Raw('collation'));
		$selectQuery->setCollateClause($c);
		$this->assertEquals($c, $selectQuery->getCollateClause());
		return $selectQuery;
	}
	
	/**
	 * @depends testCollateClause
	 * @param SelectQuery $selectQuery
	 */
	public function testToSQL92String($selectQuery)
	{
		$this->assertEquals('SELECT c1, c2 FROM "table" WHERE (w) GROUP BY g1, g2 HAVING (h) ORDER BY o1 COLLATE collation', 
			$selectQuery->toSQL92String());
	}
}