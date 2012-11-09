<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class NumericTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$numeric = new \Change\Db\Query\Expressions\Numeric();
		$this->assertNull($numeric->getValue());
		
		$numeric = new \Change\Db\Query\Expressions\Numeric(1);
		$this->assertEquals(1, $numeric->getValue());
		
		$numeric = new \Change\Db\Query\Expressions\Numeric(1.2);
		$this->assertEquals(1.2, $numeric->getValue());
	}
	
	public function testSetGetValue()
	{
		$numeric = new \Change\Db\Query\Expressions\Numeric();
		$numeric->setValue(2);
		$this->assertEquals(2, $numeric->getValue());
	}
	
	public function testPseudoString()
	{
		$numeric = new \Change\Db\Query\Expressions\Numeric();
		$numeric->setValue(2);
		$this->assertEquals("2", $numeric->pseudoQueryString());
	}
}