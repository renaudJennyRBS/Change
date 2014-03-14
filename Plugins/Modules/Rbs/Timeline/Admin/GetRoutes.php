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

		$adminManager = $event->getTarget();
		if ($adminManager instanceof \Rbs\Admin\AdminManager)
		{
			//filter edit routes
			$editRoutes = [];
			foreach ($routes as $path => $route)
			{
				if (isset($route['name']) && isset($route['model']) &&
					                           //TODO: the last OR is a fallback to 'form', remove this condition after form refactoring
					($route['name'] === 'edit' || $route['name'] === 'form'))
				{
					$editRoutes[$path] = $route;
				}
			}

			$modelManager = $event->getApplicationServices()->getModelManager();
			foreach ($editRoutes as $path => $route)
			{
				$model = $modelManager->getModelByName($route['model']);
				if ($model && $model->isEditable() && !$model->isAbstract())
				{
					$routeName = $path . '/timeline';
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
		}
		$event->setParam('routes', $routes);
	}
}