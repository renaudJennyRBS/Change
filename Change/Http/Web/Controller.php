<?php
namespace Change\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Controller
 */
class Controller extends \Change\Http\Controller
{

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 * @return void
	 */
	protected function registerDefaultListeners($eventManager)
	{
		$eventManager->addIdentifiers('Http.Web');
		$eventManager->attach(\Change\Http\Event::EVENT_RESPONSE, array($this, 'onDefaultHtmlResponse'));
	}

	/**
	 * @param $request
	 * @return \Change\Http\Event
	 */
	protected function createEvent($request)
	{
		$event = parent::createEvent($request);
		$event->setApplicationServices(new \Change\Application\ApplicationServices($this->getApplication()));
		$event->setDocumentServices(new \Change\Documents\DocumentServices($event->getApplicationServices()));
		return $event;
	}

	/**
	 * @api
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	public function createResponse()
	{
		$response = parent::createResponse();
		$response->getHeaders()->addHeaderLine('Content-Type: text/html');
		return $response;
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function onDefaultHtmlResponse($event)
	{
		$result = $event->getResult();
		if ($result instanceof \Change\Http\Result)
		{
			$response = $event->getController()->createResponse();
			$response->setStatusCode($result->getHttpStatusCode());
			$event->setResponse($response);
		}
	}
}