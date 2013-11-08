<?php

/**
 * Set error reporting to the level to which Zend Framework code must comply.
 */
error_reporting( E_ALL | E_STRICT );
if (!defined('PROJECT_HOME'))
{
	define('PROJECT_HOME', dirname(realpath(__DIR__)));
}
require_once PROJECT_HOME . '/Change/Application.php';
require_once 'Change/TestAssets/Application.php';
@unlink('UnitTestWorkspace/App/Config/project.autogen.json');
$application = new \ChangeTests\Change\TestAssets\Application();
$application->registerAutoload();
$application->clearCache();
$pluginManager = new \Change\Plugins\PluginManager();
$pluginManager->setWorkspace($application->getWorkspace());
$pluginManager->compile(false);
$application->registerPluginsAutoload();
