<?php
require_once(__DIR__ . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

$controller = new \Change\Http\Web\Controller($application);
$controller->setActionResolver(new \Change\Http\Web\Resolver());
$request = new \Change\Http\Request();

$response = $controller->handle($request);

$response->send();




