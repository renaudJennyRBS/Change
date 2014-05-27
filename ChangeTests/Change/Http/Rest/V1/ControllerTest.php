<?php
namespace ChangeTests\Change\Http\Rest\V1;

class ControllerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Http\Rest\V1\Controller
	 */
	public function testConstruct()
	{
		$application = $this->getApplication();
		$controller = new \Change\Http\Rest\V1\Controller($application);

		return $controller;

	}

	/**
	 * @depends testConstruct
	 * @param \Change\Http\Rest\V1\Controller $controller
	 * @return \Change\Http\Rest\V1\Controller
	 */
	public function testCreateResponse($controller)
	{
		$response = $controller->createResponse();
		$this->assertInstanceOf('\Zend\Http\PhpEnvironment\Response',$response);
		$ct = $response->getHeaders()->get('Content-Type');
		$this->assertInstanceOf('\Zend\Http\Header\HeaderInterface', $ct);
		$this->assertEquals('application/json', $ct->getFieldValue());
		return $controller;
	}

	/**
	 * @depends testCreateResponse
	 * @param \Change\Http\Rest\V1\Controller $controller
	 * @return \Change\Http\Rest\V1\Controller
	 */
	public function testOnException($controller)
	{
		$event = new \Change\Http\Event(\Change\Http\Event::EVENT_EXCEPTION, $controller);
		$event->setRequest(new \Change\Http\Request());
		$exception = new \RuntimeException('test message', 10);
		$exception->httpStatusCode = 501;
		$event->setParam('Exception', $exception);

		$controller->onException($event);

		$result = $event->getResult();
		$this->assertInstanceOf('\Change\Http\Rest\V1\ErrorResult', $result);

		/* @var $result \Change\Http\Rest\V1\ErrorResult */
		$this->assertEquals(501, $result->getHttpStatusCode());
		$this->assertEquals('EXCEPTION-10', $result->getErrorCode());
		$this->assertEquals('test message', $result->getErrorMessage());

		$this->assertNull($event->getResponse());

		return $event;
	}

	/**
	 * @depends testOnException
	 * @param \Change\Http\Event $event
	 * @return \Change\Http\Rest\V1\Controller
	 */
	public function testOnDefaultJsonResponse($event)
	{
		$controller = $event->getController();
		$event->getResult()->setHeaderEtag('testEtag');
		/* @var $controller \Change\Http\Rest\V1\Controller */
		$controller->onDefaultJsonResponse($event);
		$response = $event->getResponse();

		$this->assertEquals(501, $response->getStatusCode());
		$ct = $response->getHeaders()->get('Etag');
		$this->assertInstanceOf('\Zend\Http\Header\HeaderInterface', $ct);
		$this->assertEquals('testEtag', $ct->getFieldValue());

		$content = $response->getContent();
		$this->assertEquals('{"code":"EXCEPTION-10","message":"test message"}', $content);

		$event->setResult(new \Change\Http\Result());
		$event->getResult()->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_404);
		$event->getRequest()->setPath('/testPath');

		$controller->onDefaultJsonResponse($event);
		$response = $event->getResponse();
		$this->assertEquals(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
		$content = $response->getContent();
		$this->assertEquals('{"code":"PATH-NOT-FOUND","message":"Unable to resolve path","data":{"path":"\/testPath"}}', $content);

		return $controller;
	}
}