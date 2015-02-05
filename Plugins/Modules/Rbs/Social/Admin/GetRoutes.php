<?php
namespace Rbs\Social\Admin;

use Change\Events\Event;

/**
 * @name \Rbs\Social\Admin\GetRoutes
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
				if ($model && $model->isPublishable() && !$model->isAbstract())
				{
					$routeName = $path . '/social';
					if (!isset($routes[$routeName]))
					{
						$routes[$routeName] = [
							'model' => $route['model'],
							'name' => 'social',
							'rule' => [
								'templateUrl' => 'Rbs/Social/social.twig?model=' . $route['model'],
								'controller' => 'RbsChangeSocialController',
								'labelKey' => 'm.rbs.social.admin.social | ucf'
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