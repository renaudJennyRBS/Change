<?php
define('PROJECT_HOME', __DIR__);
require_once(PROJECT_HOME . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

$controller = new \Rbs\Admin\Http\Controller($application);
$controller->setActionResolver(new \Rbs\Admin\Http\Resolver());
$request = new \Change\Http\Request();
$response = $controller->handle($request);
$response->send();