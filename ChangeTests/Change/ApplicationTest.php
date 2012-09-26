<?php

namespace ChangeTests\Change;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{	
	public function testRegisterInjectionAutoload()
	{
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
}