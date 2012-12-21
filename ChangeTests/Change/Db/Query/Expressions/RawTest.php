<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class RawTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Raw();
		$this->assertNull($i->getValue());

		$i = new \Change\Db\Query\Expressions\Raw('test');
		$this->assertEquals('test', $i->getValue());
	}
	
	public function testValue()
	{
		$i = new \Change\Db\Query\Expressions\Raw();
		$i->setValue('Value');
		$this->assertEquals('Value', $i->getValue());
	}
		
	public function testToSQL92String()
	{
		$i = new \Change\Db\Query\Expressions\Raw();
		$i->setValue('Value');
		$this->assertEquals('Value', $i->toSQL92String());
		
		$i->setValue(array('12'));
		$this->assertEquals('Array', $i->toSQL92String());
	}
}