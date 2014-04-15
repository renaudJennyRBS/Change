<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
require_once(#projectPath# . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

class AnonymousRequest implements \Zend\EventManager\ListenerAggregateInterface
{
	public function attach(\Zend\EventManager\EventManagerInterface $events)
	{
		$callback = function (\Change\Http\Event $event)
		{
			$allow = $event->getApplication()->inDevelopmentMode();
			$event->getPermissionsManager()->allow($allow);
		};
		$events->attach(\Change\Http\Event::EVENT_REQUEST, $callback, 1);
	}

	public function detach(\Zend\EventManager\EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
$application->getConfiguration()->addVolatileEntry('Change/Events/Http/Rest/DEV', 'AnonymousRequest');

$controller = new \Change\Http\Rest\Controller($application);
$controller->setActionResolver(new \Change\Http\Rest\Resolver());
$request = new \Change\Http\Rest\Request();
$response = $controller->handle($request);
$response->send();