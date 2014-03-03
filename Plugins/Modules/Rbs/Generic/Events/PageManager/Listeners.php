<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\PageManager;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\PageManager\Listeners
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
		$callback = function ($event)
		{
			(new \Change\Presentation\Pages\FileCacheAdapter())->onGetCacheAdapter($event);
		};
		$events->attach(\Change\Presentation\Pages\PageManager::EVENT_GET_CACHE_ADAPTER, $callback, 5);

		$callback = function ($event)
		{
			(new \Rbs\Generic\Presentation\PageFunctions())->addFunctions($event);
		};
		$events->attach(\Change\Presentation\Pages\PageManager::EVENT_GET_FUNCTIONS, $callback, 5);
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
