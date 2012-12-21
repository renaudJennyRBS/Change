<?php
namespace ChangeTests\Change\Db\Query\Expressions;

class FakeExpression extends \Change\Db\Query\Expressions\AbstractExpression
{
	
	public function toSQL92String()
	{
		return 'FakeExpression';
	}
}

class AbstractExpressionTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new FakeExpression();
		$this->assertNull($i->getOptions());
	}

	public function testOptions()
	{
		$i = new FakeExpression();
		$i->setOptions(array('12'));
		$this->assertEquals(array('12'), $i->getOptions());
	}
}
