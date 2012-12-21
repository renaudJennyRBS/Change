<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class StringTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\String();
		$this->assertNull($i->getString());

		$i = new \Change\Db\Query\Expressions\String('test');
		$this->assertEquals('test', $i->getString());
		return $i;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testString(\Change\Db\Query\Expressions\String $i)
	{
		$i->setString('Value');
		$this->assertEquals('Value', $i->getString());
		return $i;
	}

	/**
	 * @depends testString
	 */
	public function testToSQL92String(\Change\Db\Query\Expressions\String $i)
	{
		$this->assertEquals("'Value'", $i->toSQL92String());
		$i->setString(null);
		$this->assertEquals("''", $i->toSQL92String());
	}
}