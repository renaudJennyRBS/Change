<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Ajax\V1;

use Change\Http\Event;
use Change\Http\Result;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Ajax\V1\Controller
 */
class Controller extends \Change\Http\Controller
{
	/**
	 * @api
	 * @return string
	 */
	public function getApiVersion()
	{
		return 'V1';
	}

	/**
	 * @return string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return array('Http', 'Http.Ajax', 'Http.Ajax.V1');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_REQUEST, array($this, 'onDefaultRequest'), 10);
		$eventManager->attach(Event::EVENT_EXCEPTION, array($this, 'onException'), 5);
		$eventManager->attach(Event::EVENT_RESPONSE, array($this, 'onDefaultJsonResponse'), 5);
	}

	public function onDefaultRequest(Event $event)
	{
		$event->getApplicationServices()->getPermissionsManager()->allow(false);
		$request = $event->getRequest();
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$path = $request->getPath();
		$pathPart = explode('/', $path);
		if (count($pathPart) > 2)
		{
			$LCID = $pathPart[1];
			if ($i18nManager->isSupportedLCID($LCID))
			{
				$i18nManager->setLCID($LCID);
				$request->setLCID($LCID);
				$actionPath = implode('/', array_slice($pathPart, 2));
				$method = $request->getMethod();
				$event->setParam('actionPath', $actionPath);
				$event->setParam('method', $request->getMethod());
				$event->setParam('LCID', $LCID);
				$event->getApplication()->getLogging()->info($method . ' '. $actionPath . ' (' . $LCID . ')');
			}
		}
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
				if ($result->getHttpStatusCode() && $result->getHttpStatusCode() !== HttpResponse::STATUS_CODE_200)
				{
					$error->setHttpStatusCode($result->getHttpStatusCode());
					if ($result->getHttpStatusCode() === HttpResponse::STATUS_CODE_404)
					{
						$error->setErrorCode('PATH-NOT-FOUND');
						$error->setErrorMessage('Unable to resolve path');
						$error->addDataValue('path', $event->getRequest()->getPath());
					}
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
		if ($event->getResponse())
		{
			return;
		}

		$result = $event->getResult();
		if ($result instanceof Result)
		{
			$acceptHeader = $event->getRequest()->getHeader('Accept');
			if ($acceptHeader instanceof \Zend\Http\Header\Accept && $acceptHeader->hasMediaType('application/json'))
			{
				$response = $this->createResponse();
				$response->getHeaders()->addHeaders($result->getHeaders());
				$monitoring = $this->getApplication()->getConfiguration()->getEntry('Change/Http/Ajax/Monitoring');
				if ($monitoring)
				{
					$response->getHeaders()->addHeaderLine('Change-Memory-Usage: ' . number_format(memory_get_usage()));
				}

				$response->setStatusCode($result->getHttpStatusCode());
				$event->setResponse($response);

				if ($this->resultNotModified($event->getRequest(), $result))
				{
					$response->setStatusCode(HttpResponse::STATUS_CODE_304);
				}

				$callable = [$result, 'toArray'];
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
}