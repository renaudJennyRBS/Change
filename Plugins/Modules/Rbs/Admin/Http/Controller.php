<?php
namespace Rbs\Admin\Http;

use Change\Application\ApplicationServices;
use Change\Documents\DocumentServices;
use Change\Presentation\PresentationServices;
use Change\Application;
use Change\Http\Event;
use Change\Http\Result;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Controller
 */
class Controller extends \Change\Http\Controller
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 * @return void
	 */
	protected function registerDefaultListeners($eventManager)
	{
		$eventManager->addIdentifiers('Http.Admin');
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

		$request->populateLCIDByHeader($event->getApplicationServices()->getI18nManager());
		return $event;
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultResponse($event)
	{
		$result = $event->getResult();
		$response = $event->getController()->createResponse();
		if ($result instanceof Result)
		{
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
		}
		$event->setResponse($response);
	}
}