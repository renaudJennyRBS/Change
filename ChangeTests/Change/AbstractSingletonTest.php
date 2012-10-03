<?php

namespace ChangeTests\Change;

class AbstractSingletonTest extends \PHPUnit_Framework_TestCase
{
	public function testGetInstance()
	{
		$instance = \ChangeTests\Change\TestAssets\TestSingleton::getInstance();
		$this->assertInstanceOf("\ChangeTests\Change\TestAssets\TestSingleton", $instance);
		return $instance;
	}
	
	/**
	 * @depends testGetInstance
	 */
	public function testGetInstanceAgain(\ChangeTests\Change\TestAssets\TestSingleton $singleton)
	{
		$singleton->test = "this is a test string";
		$newInstance =  \ChangeTests\Change\TestAssets\TestSingleton::getInstance();
		$this->assertInstanceOf("\ChangeTests\Change\TestAssets\TestSingleton", $newInstance);
		$this->assertEquals($singleton->test, $newInstance->test);
		$this->assertEquals($singleton, $newInstance);
		return $singleton;
	}
	
	/**
	 * @depends testGetInstance
	 */
	public function testGetReset(\ChangeTests\Change\TestAssets\TestSingleton $singleton)
	{
		\ChangeTests\Change\TestAssets\TestSingleton::reset();
		$newInstance =  \ChangeTests\Change\TestAssets\TestSingleton::getInstance();
		$this->assertInstanceOf("\ChangeTests\Change\TestAssets\TestSingleton", $newInstance);
		$this->assertNull($newInstance->test);
		$this->assertNotEquals($singleton, $newInstance);
	}
	
	/**
	 */
	public function testExtendSingleton()
	{
		$instance1 =  \ChangeTests\Change\TestAssets\TestSingleton::getInstance();
		$instance2 =  \ChangeTests\Change\TestAssets\TestExtendSingleton::getInstance();
		$this->assertInstanceOf("\ChangeTests\Change\TestAssets\TestSingleton", $instance1);
		$this->assertNotInstanceOf("\ChangeTests\Change\TestAssets\TestExtendSingleton", $instance1);
		$this->assertInstanceOf("\ChangeTests\Change\TestAssets\TestSingleton", $instance2);
		$this->assertInstanceOf("\ChangeTests\Change\TestAssets\TestExtendSingleton", $instance2);
		
	}
}