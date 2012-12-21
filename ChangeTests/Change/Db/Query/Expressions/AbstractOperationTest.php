<?php
namespace ChangeTests\Change\Db\Query\Expressions;

class FakeOperation extends \Change\Db\Query\Expressions\AbstractOperation
{
	public function toSQL92String()
	{
		return 'FakeOperation';
	}
}

class AbstractOperationTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new FakeOperation();
		$this->assertNull($i->getOptions());
		$this->assertNull($i->getOperator());
	}

	public function testOperator()
	{
		$i = new FakeOperation();
		$i->setOperator('=');
		$this->assertEquals('=', $i->getOperator());
	}
}
