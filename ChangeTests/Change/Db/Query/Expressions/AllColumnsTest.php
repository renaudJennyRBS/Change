<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class AllColumnsTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\AllColumns();
		$this->assertTrue(true);
	}
	
	public function testToSQL92String()
	{
		$i = new \Change\Db\Query\Expressions\AllColumns();
		$this->assertEquals('*', $i->toSQL92String());
	}
}
