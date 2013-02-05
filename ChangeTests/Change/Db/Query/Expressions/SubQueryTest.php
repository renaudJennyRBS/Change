<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class SubQueryTest extends \ChangeTests\Change\TestAssets\TestCase
{
	
	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->getApplication()->getApplicationServices()->getDbProvider();
	}
	
	/**
	 * @return \Change\Db\Query\SelectQuery
	 */
	protected function getSelectQuery()
	{
		$sq = new \Change\Db\Query\SelectQuery($this->getDbProvider());
		$sq->setSelectClause(new \Change\Db\Query\Clauses\SelectClause());
		$sq->getSelectClause()->addSelect(new \Change\Db\Query\Expressions\Numeric(1));
		return $sq;
	}
	
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\SubQuery($this->getSelectQuery());
		$this->assertInstanceOf('\Change\Db\Query\SelectQuery', $i->getSubQuery());
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testSubQuery(\Change\Db\Query\Expressions\SubQuery $i)
	{
		try
		{
			$i->setSubQuery('Value');
			$this->fail('Argument 1 must be an instance of Change\Db\Query\SelectQuery');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
		return $i;
	}

	/**
	 * @depends testSubQuery
	 */
	public function testToSQL92SubQuery(\Change\Db\Query\Expressions\SubQuery $i)
	{
		$this->assertEquals("(SELECT 1)", $i->toSQL92String());
	}
}