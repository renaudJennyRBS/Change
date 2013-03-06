<?php
define('PROJECT_HOME', __DIR__);
require_once(PROJECT_HOME . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

$controller = new \Change\Http\Web\Controller($application);
$controller->setActionResolver(new \Change\Http\Web\Resolver());
$request = new \Change\Http\Request();

$response = $controller->handle($request);

$response->send();




