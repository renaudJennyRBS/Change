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
		$this->assertEquals('Value', $i->toSQL92String());
		
		$i->setValue(array('12'));
		$this->assertEquals('Array', $i->toSQL92String());
	}
}