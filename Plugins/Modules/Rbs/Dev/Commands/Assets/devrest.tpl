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

$controller = new \Change\Http\Rest\V1\Controller($application);
$controller->setActionResolver(new \Change\Http\Rest\V1\Resolver());
$request = new \Change\Http\Rest\Request();

$allow = $application->inDevelopmentMode();
$anonymous = function (\Change\Http\Event $event) use ($allow)
{
	$event->getPermissionsManager()->allow($allow);
};

$controller->getEventManager()->attach(\Change\Http\Event::EVENT_REQUEST, $anonymous, 1);
$response = $controller->handle($request);
$response->send();
