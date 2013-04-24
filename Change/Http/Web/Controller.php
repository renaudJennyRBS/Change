<?php
namespace Change\Http\Web;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Change\Presentation\PresentationServices;
use Change\Http\Result;
use Zend\Http\Response as HttpResponse;
use Change\Http\Event;

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

		$callback = function ($event)
		{
			$composer = new \Change\Http\Web\Events\ComposePage();
			$composer->execute($event);
		};
		$eventManager->attach(Event::EVENT_RESULT, $callback, 5);

		$eventManager->attach(Event::EVENT_RESPONSE, array($this, 'onDefaultHtmlResponse'), 5);
	}

	/**
	 * @param $request
	 * @return \Change\Http\Event
	 */
	protected function createEvent($request)
	{
		$event = parent::createEvent($request);
		$event->setApplicationServices(new ApplicationServices($this->getApplication()));
		$event->setDocumentServices(new DocumentServices($event->getApplicationServices()));
		$event->setPresentationServices(new PresentationServices($event->getApplicationServices()));
		return $event;
	}

	/**
	 * @api
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	public function createResponse()
	{
		$response = parent::createResponse();
		$response->getHeaders()->addHeaderLine('Content-Type: text/html;charset=utf-8');
		return $response;
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function onDefaultHtmlResponse($event)
	{
		$result = $event->getResult();
		if ($result instanceof Result)
		{

			$response = $event->getController()->createResponse();
			$response->setStatusCode($result->getHttpStatusCode());
			$response->getHeaders()->addHeaders($result->getHeaders());
			if ($result instanceof \Change\Http\Web\Result\Resource)
			{
				$response->setContent($result->getContent());
			}
			else
			{
				$callable = array($result, 'toHtml');
				if (is_callable($callable))
				{
					$response->setContent(call_user_func($callable));
				}
				else
				{
					$response->setContent(strval($result));
				}
			}
			$event->setResponse($response);
		}
	}

	/**
	 * @param Event $event
	 * @return Result
	 */
	public function notFound($event)
	{
		$page = new \Change\Presentation\Themes\DefaultPage($event->getPresentationServices()->getThemeManager());
		$event->setParam('page', $page);
		$composer = new \Change\Http\Web\Events\ComposePage();
		$composer->execute($event);
		$result = $event->getResult();
		if ($result !== null)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
			return $result;
		}
		return parent::notFound($event);
	}
}