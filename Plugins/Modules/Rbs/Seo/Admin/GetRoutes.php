<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Seo\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Seo\Admin\GetRoutes
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
			//NEVER USE the routesHelper directly from adminManager! That will call this method again (infinite loop)!
			$routesHelper = new \Rbs\Admin\RoutesHelper($routes);
			$modelManager = $event->getApplicationServices()->getModelManager();

			//TODO: the last name 'form' is a fallback, remove this name after form refactoring
			$editRoutes = $routesHelper->getRoutesWithNames(['edit', 'form']);
			$urlsRoutes = $routesHelper->getRoutesWithNames(['urls']);
			$editRoutes = $routesHelper->getRoutesDiff($editRoutes, $urlsRoutes, 'model');

			foreach ($editRoutes as $path => $route)
			{
				$model = $modelManager->getModelByName($route['model']);
				if ($model && $model->isPublishable() && $model->getName() !== 'Rbs_Website_Website')
				{
					$routeName = $path . '/urls';
					if (!isset($routes[$routeName]))
					{
						$routes[$routeName] = [
							'model' => $route['model'],
							'name' => 'urls',
							'rule' => [
								'templateUrl' => 'Rbs/Admin/url-manager.twig',
								'labelKey' => 'm.rbs.admin.admin.urls | ucf'
							],
							'auto' => true
						];
					}
				}
			}
		}
		$event->setParam('routes', $routes);
	}
}