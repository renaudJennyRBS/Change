<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class ConcatTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new \Change\Db\Query\Expressions\Concat();
		$this->assertCount(0, $i->getList());
		
		$s1 = new \Change\Db\Query\Expressions\String('test');
		$i = new \Change\Db\Query\Expressions\Concat(array($s1));
		
		$this->assertCount(1, $i->getList());
	}
			
	public function testToSQL92String()
	{
		$s1 = new \Change\Db\Query\Expressions\Raw('a');
		$s2 = new \Change\Db\Query\Expressions\Raw('b');
		$i = new \Change\Db\Query\Expressions\Concat(array($s1, $s2));
		$this->assertEquals('a || b', $i->toSQL92String());
	}
}
