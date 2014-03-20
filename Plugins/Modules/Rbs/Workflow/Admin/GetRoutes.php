<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Workflow\Admin\GetRoutes
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
			$workflowRoutes = $routesHelper->getRoutesWithNames(['workflow']);
			$editRoutes = $routesHelper->getRoutesDiff($editRoutes, $workflowRoutes, 'model');

			foreach ($editRoutes as $path => $route)
			{
				$model = $modelManager->getModelByName($route['model']);
				if ($model && ($model->useCorrection() || $model->isPublishable()))
				{
					$routeName = $path . '/workflow';
					if (!isset($routes[$routeName]))
					{
						$routes[$routeName] = [
							'model' => $route['model'],
							'name' => 'workflow',
							'rule' => [
								'templateUrl' => 'Rbs/Admin/workflow/workflow.twig?model=' . $route['model'],
								'controller' => 'RbsChangeWorkflowController',
								'labelKey' => 'm.rbs.workflow.admin.workflow | ucf'
							],
							'auto' => true
						];
					}
				}
			}

			$translateRoutes = $routesHelper->getRoutesWithNames(['translate']);
			$localizedWorkflowRoutes = $routesHelper->getRoutesWithNames(['localizedWorkflow']);
			$translateRoutes = $routesHelper->getRoutesDiff($translateRoutes, $localizedWorkflowRoutes, 'model');

			foreach ($translateRoutes as $path => $route)
			{
				$model = $modelManager->getModelByName($route['model']);
				if ($model && ($model->useCorrection() || $model->isPublishable()))
				{
					$routeName = $path . '/localizedWorkflow';
					if (!isset($routes[$routeName]))
					{
						$routes[$routeName] = [
							'model' => $route['model'],
							'name' => 'localizedWorkflow',
							'rule' => [
								'templateUrl' => 'Rbs/Admin/workflow/workflow.twig?model=' . $route['model'],
								'controller' => 'RbsChangeWorkflowController',
								'labelKey' => 'm.rbs.workflow.admin.workflow | ucf'
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