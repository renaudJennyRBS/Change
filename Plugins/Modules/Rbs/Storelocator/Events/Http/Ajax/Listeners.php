<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Events\Http\Ajax;

use Change\Http\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Storelocator\Events\Http\Ajax\Listeners
 */
class Listeners implements ListenerAggregateInterface
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
		$events->attach(Event::EVENT_ACTION, [$this, 'registerActions']);
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

	/**
	 * @param Event $event
	 */
	public function registerActions(Event $event)
	{
		$actionPath = $event->getParam('actionPath');
		$request = $event->getRequest();
		if (preg_match('#^Rbs/Storelocator/Store/([0-9]+)$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setParam('storeId', intval($matches[1]));
				$event->setAction(function (Event $event) {
					(new \Rbs\Storelocator\Http\Ajax\Store())->getData($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif ('Rbs/Storelocator/Store/' == $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Storelocator\Http\Ajax\Store())->getListData($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
	}
}