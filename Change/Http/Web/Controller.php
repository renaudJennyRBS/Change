<?php
namespace Change\Http\Web;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Change\Presentation\PresentationServices;
use Change\Http\Result;
use Zend\Http\PhpEnvironment\Response;
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
		$eventManager->attach(Event::EVENT_RESPONSE, array($this, 'onDefaultResponse'), 5);
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
	 * @param $request
	 * @return \Change\Http\Web\UrlManager
	 */
	protected function getNewUrlManager($request)
	{
		$script = $request->getServer('SCRIPT_NAME');
		if (strpos($request->getRequestUri(), $script) !== 0)
		{
			$script = null;
		}
		$urlManager = new UrlManager($request->getUri(), $script);
		return $urlManager;
	}

	/**
	 * @param Event $event
	 */
	protected function validateAuthentication($event)
	{
		if (!($event->getAuthentication() instanceof \Change\Http\AuthenticationInterface))
		{
			$website = $event->getParam('website');
			if ($website instanceof \Change\Presentation\Interfaces\Website)
			{
				$authentication = new Authentication($website);
				$event->setAuthentication($authentication);
			}
		}
		parent::validateAuthentication($event);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function onDefaultResponse($event)
	{
		$result = $event->getResult();
		$response = $event->getController()->createResponse();
		if ($result instanceof Result)
		{
			$response->setStatusCode($result->getHttpStatusCode());
			$response->getHeaders()->addHeaders($result->getHeaders());

			if ($result instanceof \Change\Http\Web\Result\Page)
			{
				$acceptHeader = $event->getRequest()->getHeader('Accept');
				if ($acceptHeader instanceof \Zend\Http\Header\Accept && $acceptHeader->hasMediaType('text/html'))
				{
					$response->setContent($result->toHtml());
				}
			}
			elseif ($result instanceof \Change\Http\Web\Result\Resource)
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
		}
		$event->setResponse($response);
	}

	/**
	 * @param Event $event
	 * @return Result
	 */
	public function notFound($event)
	{
		$page = new \Change\Presentation\Themes\DefaultPage($event->getPresentationServices()->getThemeManager(), 'error404');
		$event->setParam('page', $page);
		$this->doSendResult($this->getEventManager(), $event);
		$result = $event->getResult();
		if ($result !== null)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
			return $result;
		}

		return parent::notFound($event);
	}

	/**
	 * @api
	 * @param Event $event
	 * @return Result
	 */
	public function error($event)
	{
		$page = new \Change\Presentation\Themes\DefaultPage($event->getPresentationServices()->getThemeManager(), 'error500');
		$event->setParam('page', $page);
		$this->doSendResult($this->getEventManager(), $event);
		$result = $event->getResult();
		if ($result !== null)
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_500);
			return $result;
		}
		return parent::error($event);
	}

	/**
	 * @param Event $event
	 * @return Response
	 */
	protected function getDefaultResponse($event)
	{
		$result = $this->error($event);
		$event->setResult($result);
		$this->onDefaultResponse($event);
		return $event->getResponse();
	}
}