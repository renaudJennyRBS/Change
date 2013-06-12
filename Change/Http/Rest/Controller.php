<?php
namespace Change\Http\Rest;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Change\Http\Event;
use Change\Http\Result;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\ErrorResult;

/**
 * @name \Change\Http\Rest\Controller
 */
class Controller extends \Change\Http\Controller
{

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 * @return void
	 */
	protected function registerDefaultListeners($eventManager)
	{
		$eventManager->addIdentifiers('Http.Rest');
		$eventManager->attach(Event::EVENT_EXCEPTION, array($this, 'onException'), 5);
		$eventManager->attach(Event::EVENT_RESPONSE, array($this, 'onDefaultJsonResponse'), 5);
	}

	/**
	 * @param \Change\Http\Request $request
	 * @return Event
	 */
	protected function createEvent($request)
	{
		$event = parent::createEvent($request);
		$this->initializeEvent($event);
		return $event;
	}

	/**
	 * @param Event $event
	 */
	protected function initializeEvent(Event $event)
	{
		$event->setApplicationServices(new ApplicationServices($this->getApplication()));
		$event->setDocumentServices(new DocumentServices($event->getApplicationServices()));

		$request = $event->getRequest();
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$request->populateLCIDByHeader($i18nManager);
	}

	/**
	 * @api
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	public function createResponse()
	{
		$response = parent::createResponse();
		$response->getHeaders()->addHeaderLine('Content-Type: application/json');
		return $response;
	}


	/**
	 * @param Event $event
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	protected function getDefaultResponse($event)
	{
		$response = $this->createResponse();
		$response->setStatusCode(HttpResponse::STATUS_CODE_500);
		$content = array('code' => 'ERROR-GENERIC', 'message' => 'Generic error');
		$response->setContent(json_encode($content));
		return $response;
	}

	/**
	 * @param Event $event
	 */
	public function onException($event)
	{
		/* @var $exception \Exception */
		$exception = $event->getParam('Exception');
		$result = $event->getResult();

		if (!($result instanceof ErrorResult))
		{
			$error = new ErrorResult($exception);
			if ($event->getResult() instanceof Result)
			{
				$result = $event->getResult();
				$error->setHttpStatusCode($result->getHttpStatusCode());
				if ($result->getHttpStatusCode() === HttpResponse::STATUS_CODE_404)
				{
					$error->setErrorCode('PATH-NOT-FOUND');
					$error->setErrorMessage('Unable to resolve path');
					$error->addDataValue('path', $event->getRequest()->getPath());
				}
			}

			$event->setResult($error);
			$event->setResponse(null);
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultJsonResponse($event)
	{
		$result = $event->getResult();
		if ($result instanceof Result)
		{
			$response = $event->getController()->createResponse();
			$response->getHeaders()->addHeaders($result->getHeaders());
			if ($this->getApplication()->inDevelopmentMode())
			{
				$response->getHeaders()->addHeaderLine('Change-Memory-Usage: ' . number_format(memory_get_usage()));
			}

			$response->setStatusCode($result->getHttpStatusCode());
			$event->setResponse($response);

			if ($this->resultNotModified($event->getRequest(), $result))
			{
				$response->setStatusCode(HttpResponse::STATUS_CODE_304);
			}

			$callable = array($result, 'toArray');
			if (is_callable($callable))
			{
				$data = call_user_func($callable);
				$response->setContent(json_encode($data));
			}
			elseif ($result->getHttpStatusCode() === HttpResponse::STATUS_CODE_404)
			{
				$error = new ErrorResult('PATH-NOT-FOUND', 'Unable to resolve path', HttpResponse::STATUS_CODE_404);
				$error->addDataValue('path', $event->getRequest()->getPath());
				$response->setContent(json_encode($error->toArray()));
			}
		}
	}
}