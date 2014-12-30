<?php
/**
 * Copyright (C) 2014 Ready Business System, Gaël PORT
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\AdminManager;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\AdminManager\Listeners
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
			(new \Rbs\Timeline\Admin\GetRoutes())->execute($event);
			(new \Rbs\Seo\Admin\GetRoutes())->execute($event);
			(new \Rbs\Workflow\Admin\GetRoutes())->execute($event);
		};
		//Priority 1 (default value) to be sure to get the default routes
		$events->attach('getRoutes', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Timeline\Admin\GetModelTwigAttributes())->execute($event);
			(new \Rbs\Seo\Admin\GetModelTwigAttributes())->execute($event);
			(new \Rbs\Tag\Admin\GetModelTwigAttributes())->execute($event);
			(new \Rbs\User\Admin\GetModelTwigAttributes())->execute($event);
			(new \Rbs\Website\Admin\GetModelTwigAttributes())->execute($event);
		};
		//Priority 1 (default value) to be sure to get the default attributes
		$events->attach('getModelTwigAttributes', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Generic\Commands\InitializeWebsite())->getGenericSettingsStructures($event);
		};
		$events->attach('getGenericSettingsStructures', $callback, 5);

		$callback = function ($event)
		{
			(new \Rbs\User\Admin\SearchDocuments())->execute($event);
			(new \Rbs\Geo\Admin\AdminManager())->onSearchDocument($event);
		};
		$events->attach('searchDocuments', $callback, 10);

		$callback = function ($event)
		{
			(new \Rbs\Geo\Admin\AdminManager())->onGetHomeAttributes($event);
		};
		$events->attach('getHomeAttributes', $callback, 10);
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