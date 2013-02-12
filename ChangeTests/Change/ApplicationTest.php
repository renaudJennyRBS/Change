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
		//$this->assertFalse(class_exists('\ZendOAuth\OAuth'));
		$this->assertFalse(class_exists('\Change\Stdlib\File'));
		require_once PROJECT_HOME . '/Change/Application.php';
		$application = new \Change\Application();
		$application->registerNamespaceAutoload();
		$this->assertTrue(class_exists('\Zend\Stdlib\ErrorHandler'));
		//$this->assertTrue(class_exists('\ZendOAuth\OAuth'));
		$this->assertTrue(class_exists('\Change\Stdlib\File'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testRegisterInjectionAutoload()
	{
		if (!defined('PROJECT_HOME'))
		{
			define('PROJECT_HOME',  dirname(dirname(realpath(__DIR__))));
		}
		require_once PROJECT_HOME . '/Change/Application.php';
		require_once 'TestAssets/Application.php';

		$application = new \ChangeTests\Change\TestAssets\Application();
		$application->registerCoreAutoload();
		$application->registerNamespaceAutoload();

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
		$application->registerInjectionAutoload(true);
		$injection = new \Change\Injection\ClassInjection($originalInfo, $replacingInfos);
		$injection->setWorkspace($application->getWorkspace());
		$injection->compile();
		$instance = new \ChangeTests\Change\TestAssets\OriginalClass();
		$this->assertEquals($instance->test(), 'InjectingClass');
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testStart()
	{
		if (!defined('PROJECT_HOME'))
		{
			define('PROJECT_HOME',  dirname(dirname(realpath(__DIR__))));
		}
		require_once PROJECT_HOME . '/Change/Application.php';
		require_once 'TestAssets/Application.php';

		$application = new \ChangeTests\Change\TestAssets\Application();
		$application->registerCoreAutoload();
		$application->registerNamespaceAutoload();
		$application->start();
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
		$injection->setWorkspace($application->getWorkspace());
		$injection->compile();
		$instance = new \ChangeTests\Change\TestAssets\OriginalClass();
		$this->assertEquals($instance->test(), 'InjectingClass');
		$this->assertTrue(class_exists('\Zend\Stdlib\ErrorHandler'));
		//$this->assertTrue(class_exists('\ZendOAuth\OAuth'));
		$this->assertTrue(class_exists('\Change\Stdlib\File'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetProfile()
	{
		if (!defined('PROJECT_HOME'))
		{
			define('PROJECT_HOME',  dirname(dirname(realpath(__DIR__))));
		}
		require_once PROJECT_HOME . '/Change/Application.php';
		require_once 'TestAssets/Application.php';

		$application = new \ChangeTests\Change\TestAssets\Application();
		$application->registerCoreAutoload();
		$application->registerNamespaceAutoload();

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
	}


	public function testGetConfiguration()
	{
		$app = new \ChangeTests\Change\TestAssets\Application();
		$this->assertInstanceOf('\Change\Configuration\Configuration', $app->getConfiguration());
	}

	public function testGetWorkspace()
	{
		$app = new \ChangeTests\Change\TestAssets\Application();
		$this->assertInstanceOf('\Change\Workspace', $app->getWorkspace());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testStartWithBootstrapClass()
	{
		if (!defined('PROJECT_HOME'))
		{
			define('PROJECT_HOME',  dirname(dirname(realpath(__DIR__))));
		}
		require_once PROJECT_HOME . '/Change/Application.php';
		require_once 'TestAssets/Application.php';

		$application = new \ChangeTests\Change\TestAssets\Application();
		$application->registerCoreAutoload();

		$this->assertFalse(defined('TESTBOOTSTRAP_OK'));
		require_once __DIR__ . '/TestAssets/TestBootstrap.php';
		$application->start('\ChangeTests\Change\TestAssets\TestBootstrap');
		$this->assertTrue(defined('TESTBOOTSTRAP_OK'));
	}
}