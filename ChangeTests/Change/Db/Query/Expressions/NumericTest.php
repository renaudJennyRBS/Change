<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class NumericTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Numeric();
		$this->assertNull($i->getValue());

		$i = new \Change\Db\Query\Expressions\Numeric('test');
		$this->assertEquals('test', $i->getValue());
	}
	
	public function testValue()
	{
		$i = new \Change\Db\Query\Expressions\Numeric();
		$i->setValue('Value');
		$this->assertEquals('Value', $i->getValue());
	}
		
	public function testToSQL92String()
	{
		$i = new \Change\Db\Query\Expressions\Numeric();
		$i->setValue('Value');
		$this->assertEquals('0', $i->toSQL92String());
		
		$i->setValue('3.3');
		$this->assertEquals('3.3', $i->toSQL92String());
	}
}