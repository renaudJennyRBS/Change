<?php
namespace Rbs\Admin\Http\Actions;

use Change\Http\Event;

/**
 * @name \Rbs\Admin\Http\Actions\GetRoutes
 */
class GetRoutes
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		/* @var $manager \Rbs\Admin\Manager */
		$manager = $event->getParam('manager');
		$routes = $manager->getRoutes();

		//WildCard :IDENTIFIER at last
		krsort($routes);

		$result = new \Rbs\Admin\Http\Result\Renderer();
		$result->setHeaderContentType('application/javascript');
		$result->setRenderer(function() use ($routes)
		{
			if (count($routes))
			{
				return '(function () {
	"use strict";
	__change.routes = ' . json_encode($routes, JSON_UNESCAPED_SLASHES) . ';
})();';
			}
			else
			{
				return '(function () {
	"use strict";
	__change.routes = {};
})();';
			}
		});
		$event->setResult($result);
	}
}