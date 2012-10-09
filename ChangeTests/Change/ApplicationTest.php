<?php

namespace ChangeTests\Change;
/**
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{	
	
	public function run(\PHPUnit_Framework_TestResult $result = NULL)
	{
		$this->setPreserveGlobalState(false);
		return parent::run($result);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testNamespaceAutoload()
	{
		if (!defined('PROJECT_HOME'))
		{
			define('PROJECT_HOME',  dirname(dirname(realpath(__DIR__))));
		}
		$this->assertFalse(class_exists('\Zend\Stdlib\ErrorHandler'));
		$this->assertFalse(class_exists('\ZendOAuth\OAuth'));
		$this->assertFalse(class_exists('\Change\Stdlib\File'));
		require_once PROJECT_HOME . '/Change/Application.php';
		\Change\Application::getInstance()->registerNamespaceAutoload();
		$this->assertTrue(class_exists('\Zend\Stdlib\ErrorHandler'));
		$this->assertTrue(class_exists('\ZendOAuth\OAuth'));
		$this->assertTrue(class_exists('\Change\Stdlib\File'));
	}
	
	/**
	 * @runInSeparateProcess
	 */
	public function testRegisterInjectionAutoload()
	{
		require_once dirname(realpath(__DIR__)) . '/Bootstrap.php';
		\Change\Application::getInstance()->registerNamespaceAutoload();
		
		$originalInfo = array(	
			'name' => '\ChangeTests\Change\TestAssets\OriginalClass',
			'path' => __DIR__ . '/TestAssets/OriginalClass.php'
		);
		$replacingInfos = 	array(
			array(
				'name' => '\ChangeTests\Change\TestAssets\InjectingClass',
				'path' => __DIR__ . '/TestAssets/InjectingClass.php'
			),
		);
		\Change\Application::getInstance()->registerInjectionAutoload(true);
		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfos);
		$injection->compile();
		$instance = new \ChangeTests\Change\TestAssets\OriginalClass();
		$this->assertEquals($instance->test(), 'InjectingClass');
	}
	
	/**
	 * @runInSeparateProcess
	 */
	public function testStart()
	{
		require_once dirname(realpath(__DIR__)) . '/Bootstrap.php';
		\Change\Application::getInstance()->start();
		$originalInfo = array(
			'name' => '\ChangeTests\Change\TestAssets\OriginalClass',
			'path' => __DIR__ . '/TestAssets/OriginalClass.php'
		);
		$replacingInfos = 	array(
			array(
				'name' => '\ChangeTests\Change\TestAssets\InjectingClass',
				'path' => __DIR__ . '/TestAssets/InjectingClass.php'
			),
		);
		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfos);
		$injection->compile();
		$instance = new \ChangeTests\Change\TestAssets\OriginalClass();
		$this->assertEquals($instance->test(), 'InjectingClass');
		$this->assertTrue(class_exists('\Zend\Stdlib\ErrorHandler'));
		$this->assertTrue(class_exists('\ZendOAuth\OAuth'));
		$this->assertTrue(class_exists('\Change\Stdlib\File'));
	}
	
	/**
	 * @runInSeparateProcess
	 */
	public function testGetProfile()
	{
		require_once dirname(realpath(__DIR__)) . '/Bootstrap.php';
		$this->assertEquals('default', \Change\Application::getInstance()->getProfile());
	}
}