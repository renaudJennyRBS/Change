<?php
namespace ChangeTests\Change\Http;

use Change\Http\Controller;
use ChangeTests\Change\Http\TestAssets\FakeEventHandler;

class ControllerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Http\Controller
	 */
	public function testConstruct()
	{
		$application = $this->getApplication();
		$controller = new Controller($application);
		$this->assertInstanceOf('\Change\Events\EventManager', $controller->getEventManager());
		$this->assertSame(array('Http'), $controller->getEventManager()->getIdentifiers());
		return $controller;
	}

	/**
	 * @depends testConstruct
	 * @param \Change\Http\Controller $controller
	 * @return \Change\Http\Controller
	 */
	public function testActionResolver($controller)
	{
		$ac = new \Change\Http\BaseResolver();

		$controller->setActionResolver($ac);

		$this->assertSame($ac, $controller->getActionResolver());

		return $controller;
	}

	/**
	 * @depends testConstruct
	 * @param \Change\Http\Controller $controller
	 * @return \Change\Http\Controller
	 */
	public function testCreateResponse($controller)
	{
		$response = $controller->createResponse();
		$this->assertInstanceOf('\Zend\Http\PhpEnvironment\Response', $response);
		return $controller;
	}

	/**
	 * @depends testConstruct
	 * @param \Change\Http\Controller $controller
	 * @return \Change\Http\Controller
	 */
	public function testHandle($controller)
	{
		$eventManager = $controller->getEventManager();
		$fakeEventHandler  = new FakeEventHandler();

		$eventManager->attach(\Change\Http\Event::EVENT_REQUEST, function($event) use ($fakeEventHandler) {$fakeEventHandler->onRequest($event);}, 5);
		$eventManager->attach(\Change\Http\Event::EVENT_ACTION, array($fakeEventHandler, 'onAction'), 5);
		$eventManager->attach(\Change\Http\Event::EVENT_RESULT, array($fakeEventHandler, 'onResult'), 5);
		$eventManager->attach(\Change\Http\Event::EVENT_RESPONSE, array($fakeEventHandler, 'onResponse'), 5);
		$eventManager->attach(\Change\Http\Event::EVENT_EXCEPTION, array($fakeEventHandler, 'onException'), 5);

		$request = new \Change\Http\Request();
		$response = $controller->handle($request);

		$this->assertInstanceOf('\Zend\Http\PhpEnvironment\Response', $response);
		$this->assertEquals(500, $response->getStatusCode());
		$this->assertCount(5, $fakeEventHandler->callNames);
		$this->assertEquals(array('onRequest', 'onAction', 'execute', 'onResult', 'onResponse'), $fakeEventHandler->callNames);

		$fakeEventHandler->setThrowOn('onRequest');
		$response = $controller->handle($request);
		$this->assertInstanceOf('\Zend\Http\PhpEnvironment\Response', $response);
		$this->assertEquals(500, $response->getStatusCode());
		$this->assertCount(3, $fakeEventHandler->callNames);
		$this->assertEquals(array('onRequest', 'onException(10000)', 'onResponse'), $fakeEventHandler->callNames);

		$fakeEventHandler->setThrowOn('onAction');
		$response = $controller->handle($request);
		$this->assertInstanceOf('\Zend\Http\PhpEnvironment\Response', $response);
		$this->assertEquals(500, $response->getStatusCode());
		$this->assertCount(4, $fakeEventHandler->callNames);
		$this->assertEquals(array('onRequest', 'onAction', 'onException(10001)', 'onResponse'), $fakeEventHandler->callNames);

		$fakeEventHandler->setThrowOn('execute');
		$response = $controller->handle($request);
		$this->assertInstanceOf('\Zend\Http\PhpEnvironment\Response', $response);
		$this->assertEquals(500, $response->getStatusCode());
		$this->assertCount(5, $fakeEventHandler->callNames);
		$this->assertEquals(array('onRequest', 'onAction', 'execute', 'onException(10005)', 'onResponse'), $fakeEventHandler->callNames);

		$fakeEventHandler->setThrowOn('onResult');
		$response = $controller->handle($request);
		$this->assertInstanceOf('\Zend\Http\PhpEnvironment\Response', $response);
		$this->assertEquals(500, $response->getStatusCode());
		$this->assertCount(6, $fakeEventHandler->callNames);
		$this->assertEquals(array('onRequest', 'onAction', 'execute', 'onResult', 'onException(10002)', 'onResponse'), $fakeEventHandler->callNames);


		$fakeEventHandler->setThrowOn('onResponse');
		$response = $controller->handle($request);
		$this->assertInstanceOf('\Zend\Http\PhpEnvironment\Response', $response);
		$this->assertEquals(500, $response->getStatusCode());

		$this->assertCount(7, $fakeEventHandler->callNames);
		$this->assertEquals(array('onRequest', 'onAction', 'execute', 'onResult', 'onResponse', 'onException(10003)', 'onResponse'),
			$fakeEventHandler->callNames);

		return $controller;
	}
}