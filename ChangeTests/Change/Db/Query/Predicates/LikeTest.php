<?php
namespace ChangeTests\Change\Db\Query\Predicates;

use Change\Db\Query\Predicates\Like;

class LikeTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new Like();
		$this->assertInstanceOf('\Change\Db\Query\Predicates\BinaryPredicate', $i);
		$this->assertNull($i->getLeftHandExpression());
		$this->assertNull($i->getRightHandExpression());
		$this->assertFalse($i->getCaseSensitive());
		$this->assertEquals('LIKE', $i->getOperator());	
		$this->assertEquals(Like::ANYWHERE, $i->getMatchMode());
	}
	
	public function testMatchMode()
	{
		$i = new Like();
		foreach (array(Like::ANYWHERE, Like::BEGIN, Like::END, Like::EXACT) as $matchMode)
		{
			$i->setMatchMode($matchMode);
			$this->assertEquals($matchMode, $i->getMatchMode());
		}
		
		try
		{
			$i->setMatchMode(null);
			$this->fail('Argument 1 must be a valid const');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testCaseSensitive()
	{
		$i = new Like();
		$i->setCaseSensitive(true);
		$this->assertTrue($i->getCaseSensitive());
		$this->assertEquals('LIKE BINARY', $i->getOperator());

		$i->setCaseSensitive(false);
		$this->assertFalse($i->getCaseSensitive());
		$this->assertEquals('LIKE', $i->getOperator());	
	}
	
	public function testToSQL92String()
	{		
		$i = new Like();
		$i->setLeftHandExpression(new \Change\Db\Query\Expressions\Raw('lhe'));
		$i->setRightHandExpression(new \Change\Db\Query\Expressions\Raw('rhe'));
		$this->assertEquals("lhe LIKE '%' || rhe || '%'", $i->toSQL92String());
		$i->setCaseSensitive(true);
		$this->assertEquals("lhe LIKE BINARY '%' || rhe || '%'", $i->toSQL92String());
		
		$i->setMatchMode(Like::BEGIN);
		$this->assertEquals("lhe LIKE BINARY rhe || '%'", $i->toSQL92String());
		
		$i->setMatchMode(Like::END);
		$this->assertEquals("lhe LIKE BINARY '%' || rhe", $i->toSQL92String());
		
		$i->setMatchMode(Like::EXACT);
		$this->assertEquals("lhe LIKE BINARY rhe", $i->toSQL92String());
	}
}
