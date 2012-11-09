<?php

namespace ChangeTests\Change\Db\Query\Expressions;

use Change\Db\Query\Expressions\String;

class StringTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$string = new \Change\Db\Query\Expressions\String();
		$this->assertNull($string->getString());
		
		$string = new \Change\Db\Query\Expressions\String('tutu');
		$this->assertEquals('tutu', $string->getString());
	}
	
	public function testSetGetString()
	{
		$string = new \Change\Db\Query\Expressions\String();
		$string->setString('test');
		$this->assertEquals('test', $string->getString());
	}
	
	public function testPseudoString()
	{
		$string = new \Change\Db\Query\Expressions\String();
		$string->setString('test');
		$this->assertEquals("test", $string->pseudoQueryString());
	}
}