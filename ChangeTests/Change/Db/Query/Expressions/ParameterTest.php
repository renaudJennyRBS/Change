<?php

namespace ChangeTests\Change\Db\Query\Expressions;

use Change\Db\Query\Expressions\Parameter;

class ParameterTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new Parameter('test');
		$this->assertEquals('test', $i->getName());
		$this->assertEquals(Parameter::STRING, $i->getType());
	}
	
	
	public function testType()
	{
		$i = new Parameter('test');
		foreach (array(Parameter::STRING, Parameter::NUMERIC, Parameter::DATETIME, Parameter::LOB) as $value)
		{
			$i->setType($value);
			$this->assertEquals($value, $i->getType());
		}

		try
		{
			$i->setType(null);
			$this->fail('Argument 1 must be a valid const');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
		
	public function testToSQL92String()
	{
		$i = new \Change\Db\Query\Expressions\Parameter('test');
		$this->assertEquals(':test', $i->toSQL92String());
	}
}