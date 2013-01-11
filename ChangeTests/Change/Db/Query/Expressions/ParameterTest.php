<?php

namespace ChangeTests\Change\Db\Query\Expressions;

use Change\Db\Query\Expressions\Parameter;
use Change\Db\ScalarType;

class ParameterTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new Parameter('test');
		$this->assertEquals('test', $i->getName());
		$this->assertEquals(ScalarType::STRING, $i->getType());
	}
	
	
	public function testType()
	{
		$i = new Parameter('test');
		foreach (array(ScalarType::STRING, ScalarType::BOOLEAN, ScalarType::DATETIME, 
			ScalarType::LOB, ScalarType::TEXT, ScalarType::DECIMAL, ScalarType::INTEGER) as $value)
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