<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Timeline\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Timeline\Admin\GetRoutes
 */
class GetRoutes
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$routes = $event->getParam('routes');
		//routes shouldn't be empty
		if (!is_array($routes))
		{
			return;
		}

		//NEVER USE the routesHelper directly from adminManager! That will call this method again (infinite loop)!
		$routesHelper = new \Rbs\Admin\RoutesHelper($routes);

		$editRoutes = $routesHelper->getRoutesWithNames(['edit']);
		$timelineRoutes = $routesHelper->getRoutesWithNames(['timeline']);
		$editRoutes = $routesHelper->getRoutesDiff($editRoutes, $timelineRoutes, 'model');

		foreach ($editRoutes as $path => $route)
		{
			$routeName = $path . '/timeline';
			if (!isset($routes[$routeName]))
			{
				$routes[$routeName] = [
					'model' => $route['model'],
					'name' => 'timeline',
					'rule' => [
						'templateUrl' => 'Rbs/Timeline/timeline.twig?model=' . $route['model'],
						'controller' => 'RbsChangeTimelineController',
						'labelKey' => 'm.rbs.timeline.admin.timeline | ucf'
					],
					'auto' => true
				];
			}
		}
		$event->setParam('routes', $routes);
	}
}