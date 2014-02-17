<?php
require_once(__DIR__ . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

$controller = new \Change\Http\Rest\Controller($application);
$controller->setActionResolver(new \Change\Http\Rest\Resolver());
$request = new \Change\Http\Rest\Request();

$response = $controller->handle($request);

$response->send();





