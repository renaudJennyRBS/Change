<?php
namespace ChangeTests\Change\Db\Query\Predicates;

use Change\Db\Query\Predicates\Exists;

class ExistsTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstruct()
	{
		$obj = new Exists();
		$this->assertInstanceOf('\Change\Db\Query\Predicates\UnaryPredicate', $obj);
		$this->assertNull($obj->getExpression());
		$this->assertFalse($obj->getNot());
		$this->assertEquals('EXISTS', $obj->getOperator());
	}

	public function testNot()
	{
		$obj = new Exists();
		$obj->setNot(true);
		$this->assertTrue($obj->getNot());
		$this->assertEquals('NOT EXISTS', $obj->getOperator());

		$obj->setNot(false);
		$this->assertFalse($obj->getNot());
		$this->assertEquals('EXISTS', $obj->getOperator());
	}
	
	public function testCheckCompile()
	{
		$obj = new Exists();
		try
		{			
			$obj->checkCompile();
			$this->fail('Exception Expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Expression must be a SubQuery', $e->getMessage());
		}
	}

	public function testToSQL92String()
	{
		$sq = new \Change\Db\Query\SelectQuery();
		$sq->setSelectClause(
			new \Change\Db\Query\Clauses\SelectClause(new \Change\Db\Query\Expressions\ExpressionList(
				array(new \Change\Db\Query\Expressions\Column(
					new \Change\Db\Query\Expressions\Identifier(array('c1'))))
			)
			));
		$subQuery = new \Change\Db\Query\Expressions\SubQuery($sq);
		$obj = new Exists($subQuery);
		$this->assertEquals("EXISTS(SELECT \"c1\")", $obj->toSQL92String());
	}
}
