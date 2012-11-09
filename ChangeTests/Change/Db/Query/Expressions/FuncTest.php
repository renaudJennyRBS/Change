<?php

namespace ChangeTests\Change\Db\Query\Expressions;

class FuncTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$func = new \Change\Db\Query\Expressions\Func();
		$this->assertNull($func->getFunctionName());
		$this->assertEmpty($func->getArguments());
		
		$func = new \Change\Db\Query\Expressions\Func('TRIM');
		$this->assertEquals('TRIM', $func->getFunctionName());
		$this->assertEmpty($func->getArguments());
		
		$func = new \Change\Db\Query\Expressions\Func('TRIM', array('tutu'));
		$this->assertEquals('TRIM', $func->getFunctionName());
		$this->assertCount(1, $func->getArguments());
		$this->assertContains('tutu', $func->getArguments());
	}
	
	public function testGetSetFunctionName()
	{
		$func = new \Change\Db\Query\Expressions\Func();
		$func->setFunctionName('TRIM');
		$this->assertEquals('TRIM', $func->getFunctionName());
	}
	
	public function testGetSetArguments()
	{
		$func = new \Change\Db\Query\Expressions\Func('TRIM');
		$func->setArguments(array('a', 'b', 'c'));
		$this->assertCount(3, $func->getArguments());
		$this->assertContains('a', $func->getArguments());
		$this->assertContains('b', $func->getArguments());
		$this->assertContains('c', $func->getArguments());
	}
	
	public function testPseudoString()
	{
		$func = new \Change\Db\Query\Expressions\Func('TRIM');
		$func->setArguments(array(new \Change\Db\Query\Expressions\String('tutu')));
		$this->assertEquals('TRIM(tutu)', $func->pseudoQueryString());
	}
}