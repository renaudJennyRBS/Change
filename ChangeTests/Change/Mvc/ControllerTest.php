<?php

namespace Tests\Change\Mvc;

class ControllerTest extends \PHPUnit_Framework_TestCase
{
	
	/**
	 */
	protected function setUp()
	{
		spl_autoload_register(array($this, "fakeAutoload"));
		require_once PROJECT_HOME . '/framework/Framework.php';
		\Framework::registerChangeAutoload();
	}
	
	
	public function fakeAutoload($className)
	{
		if ($className === 'Change\Fakemodule\Actions\Fakeaction' || $className === 'Change\Fakemodule\Actions\Fakesecureaction')
		{
			require_once  __DIR__ . '/TestAssets/Fakeaction.php';
			return true;
		}
		elseif ($className === 'Change\Fakemodule\Views\Fakeview')
		{
			require_once  __DIR__ . '/TestAssets/Fakeview.php';
			return true;
		}
		elseif ($className === 'Change\Website\Actions\Error404')
		{
			require_once  __DIR__ . '/TestAssets/ForwardAction.php';
			return true;
		}
		elseif ($className === 'Change\Users\Actions\Login')
		{
			require_once  __DIR__ . '/TestAssets/ForwardAction.php';
			return true;
		}
		return false;
	}
	
	
	public function testConstruct()
	{
		$controller = new \Change\Mvc\Controller();
		$this->assertInstanceOf('\Change\Mvc\Context', $controller->getContext());
		$this->assertInstanceOf('\Change\Mvc\Request', $controller->getRequest());
		$this->assertInstanceOf('\Change\Mvc\User', $controller->getUser());
		$this->assertInstanceOf('\Change\Mvc\Storage', $controller->getStorage());
		return $controller;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testActionExists(\Change\Mvc\Controller $controller)
	{
		$this->assertFalse($controller->actionExists('fakemodule', 'Fakeactionnotfound'));
		$this->assertTrue($controller->actionExists('fakemodule', 'Fakeaction'));
		$this->assertTrue($controller->actionExists('fakemodule', 'Fakesecureaction'));
		return $controller;
	}

	/**
	 * @depends testActionExists
	 */
	public function testViewExists(\Change\Mvc\Controller $controller)
	{
		$this->assertFalse($controller->viewExists('fakemodule', 'Fakeviewnotfound'));
		$this->assertTrue($controller->viewExists('fakemodule', 'Fakeview'));
		return $controller;
	}
	
	/**
	 * @depends testViewExists
	 */
	public function testForward(\Change\Mvc\Controller $controller)
	{
		ob_start();
		$controller->forward('fakemodule', 'Fakeaction');
		$result = ob_get_clean();
		$this->assertEquals('Fakemodule\Fakeaction', $result);

		ob_start();
		$controller->forward('fakemodule', 'Fakesecureaction');
		$result = ob_get_clean();
		$this->assertEquals('Users\Login', $result);
		
		ob_start();
		$controller->forward('fakemodule', 'Fakeactionnotfound');
		$result = ob_get_clean();
		$this->assertEquals('Website\Error404', $result);

	}
}