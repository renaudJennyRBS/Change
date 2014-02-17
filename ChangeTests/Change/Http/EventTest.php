<?php
namespace ChangeTests\Change\Http;

class EventTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Http\Event
	 */
	public function testConstruct()
	{
		$event = new \Change\Http\Event();
		$this->assertInstanceOf('\Change\Http\Event', $event);
		return $event;
	}

	/**
	 * @depends testConstruct
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Event
	 */
	public function testController($event)
	{
		$application = $this->getApplication();
		$controller = new \Change\Http\Controller($application);
		$event->setTarget($controller);
		$this->assertSame($controller, $event->getController());
		return $event;
	}

	/**
	 * @depends testController
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Event
	 */
	public function testApplication($event)
	{
		$this->assertNull($event->getApplication());
		$event->setParam('application', $this->getApplication());
		$this->assertSame($this->getApplication(), $event->getApplication());
		return $event;
	}


	/**
	 * @depends testApplication
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Event
	 */
	public function testApplicationServices($event)
	{
		$this->assertNull($event->getApplicationServices());
		$event->setParams($this->getDefaultEventArguments());
		$this->assertSame($this->getApplicationServices(), $event->getApplicationServices());
		return $event;
	}

	/**
	 * @depends testApplicationServices
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Event
	 */
	public function testRequest($event)
	{
		$this->assertNull($event->getRequest());
		$request = new \Change\Http\Request();
		$event->setRequest($request);
		$this->assertSame($request, $event->getRequest());
		return $event;
	}

	/**
	 * @depends testRequest
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Event
	 */
	public function testUrlManager($event)
	{
		$this->assertNull($event->getUrlManager());

		$uri = new \Zend\Uri\Http();
		$uri->parse('http://domain.net');
		$urlManager = new \Change\Http\UrlManager($uri);

		$event->setUrlManager($urlManager);
		$this->assertSame($urlManager, $event->getUrlManager());
		return $event;
	}

	/**
	 * @depends testUrlManager
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Event
	 */
	public function testAction($event)
	{
		$this->assertNull($event->getAction());

		$action = array($this, 'testAction');

		$event->setAction($action);
		$this->assertSame($action, $event->getAction());

		return $event;
	}

	/**
	 * @depends testAction
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Event
	 */
	public function testResult($event)
	{
		$this->assertNull($event->getResult());

		$result = new \Change\Http\Result();
		$event->setResult($result);
		$this->assertSame($result, $event->getResult());

		return $event;
	}

	/**
	 * @depends testResult
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Event
	 */
	public function testResponse($event)
	{
		$this->assertNull($event->getResponse());

		$response = new \Zend\Http\PhpEnvironment\Response();
		$event->setResponse($response);
		$this->assertSame($response, $event->getResponse());

		return $event;
	}
}