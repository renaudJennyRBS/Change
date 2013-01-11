<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class StringTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\String();
		$this->assertInstanceOf('\Change\Db\Query\Expressions\Value', $i);
		$this->assertEquals(\Change\Db\ScalarType::STRING, $i->getScalarType());

		$i = new \Change\Db\Query\Expressions\String('test');
		$this->assertEquals('test', $i->getValue());
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testToSQL92String(\Change\Db\Query\Expressions\String $i)
	{
		$this->assertEquals("'test'", $i->toSQL92String());
		
		$i->setValue(null);
		$this->assertEquals("NULL", $i->toSQL92String());
		
		$i->setValue("t'est");
		$this->assertEquals("'t\\'est'", $i->toSQL92String());
	}
}