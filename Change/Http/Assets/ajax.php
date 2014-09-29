<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
require_once(__DIR__ . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

$controller = new \Change\Http\Ajax\V1\Controller($application);
$controller->setActionResolver(new \Change\Http\Ajax\V1\Resolver());
$request = new \Change\Http\Ajax\Request();

$response = $controller->handle($request);

$response->send();