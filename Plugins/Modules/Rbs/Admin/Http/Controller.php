<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http;

use Change\Http\Event;
use Change\Http\Result;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Controller
 */
class Controller extends \Change\Http\Controller
{

	/**
	 * @return string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return array('Http', 'Http.Admin');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_REQUEST, array($this, 'onDefaultRequest'), 5);
		$eventManager->attach(Event::EVENT_RESPONSE, array($this, 'onDefaultResponse'), 5);
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultRequest(Event $event)
	{
		$request = $event->getRequest();
		$applicationServices = $event->getApplicationServices();
		$applicationServices->getPermissionsManager()->allow(true);
		$manager = new \Rbs\Admin\Manager();
		$manager->setApplication($this->getApplication())
			->setI18nManager($applicationServices->getI18nManager())
			->setModelManager($applicationServices->getModelManager())
			->setPluginManager($applicationServices->getPluginManager());
		$event->setParam('manager', $manager);

		$request->populateLCIDByHeader($applicationServices->getI18nManager());
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