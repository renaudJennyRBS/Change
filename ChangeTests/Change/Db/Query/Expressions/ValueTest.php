<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class ValueTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Value();
		$this->assertNull($i->getValue());
		$this->assertTrue($i->isNull());
		$this->assertEquals(\Change\Db\ScalarType::STRING, $i->getScalarType());

		$i = new \Change\Db\Query\Expressions\Value(45.6, \Change\Db\ScalarType::DECIMAL);
		$this->assertEquals(45.6, $i->getValue());
		$this->assertEquals(\Change\Db\ScalarType::DECIMAL, $i->getScalarType());
		$this->assertFalse($i->isNull());
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testValue(\Change\Db\Query\Expressions\Value $i)
	{
		$i->setValue('Value');
		$this->assertEquals('Value', $i->getValue());
		
		$i->setScalarType(\Change\Db\ScalarType::STRING);
		$this->assertEquals(\Change\Db\ScalarType::STRING, $i->getScalarType());
		
		return $i;
	}

	/**
	 * @depends testValue
	 */
	public function testToSQL92String(\Change\Db\Query\Expressions\Value $i)
	{
		$this->assertEquals("'Value'", $i->toSQL92String());
		$i->setValue(null);
		$this->assertEquals("NULL", $i->toSQL92String());
		
		$i->setValue("test'");
		$this->assertEquals("'test\\''", $i->toSQL92String());
	}
}