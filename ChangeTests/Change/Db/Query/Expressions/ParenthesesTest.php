<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class ParenthesesTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Parentheses(new \Change\Db\Query\Expressions\Raw('test'));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $i->getExpression());

		try
		{
			new \Change\Db\Query\Expressions\Parentheses('test');
			$this->fail('Argument 1 must be an instance of Expressions\AbstractExpression');
		}
		catch (\Exception $e)
		{
			$this->assertStringStartsWith('Argument 1', $e->getMessage());
		}
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testExpression(\Change\Db\Query\Expressions\Parentheses $i)
	{
		$i->setExpression(new \Change\Db\Query\Expressions\Raw('set'));
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Raw', $i->getExpression());
		$this->assertEquals('set', $i->getExpression()->getValue());
		return $i;
	}
		
	/**
	 * @depends testExpression
	 */
	public function testToSQL92String(\Change\Db\Query\Expressions\Parentheses $i)
	{
		$this->assertEquals('(set)', $i->toSQL92String());
	}
}