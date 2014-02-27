<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\OAuth;

use Change\Http\Event as HttpEvent;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Change\Http\Rest\OAuth\ListenerAggregate
 */
class ListenerAggregate implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callBack = function ($event)
		{
			$l = new AuthenticationListener();
			$l->onRequest($event);
		};
		$events->attach(array(HttpEvent::EVENT_REQUEST), $callBack, 5);

		$callBack = function ($event)
		{
			$l = new AuthenticationListener();
			$l->onResponse($event);
		};
		$events->attach(array(HttpEvent::EVENT_RESPONSE), $callBack, 10);

		$callBack = function (HttpEvent $event)
		{
			$l = new AuthenticationListener();
			try
			{
				$l->onAuthenticate($event);
			}
			catch (\RuntimeException $e)
			{
				$event->getApplicationServices()->getLogging()->exception($e);
			}
		};
		$events->attach(array(HttpEvent::EVENT_AUTHENTICATE), $callBack, 10);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
