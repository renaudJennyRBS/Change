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
\Change\Application::getInstance()->registerNamespaceAutoload();
$zendLoader  = new \Zend\Loader\StandardAutoloader();
$zendLoader->registerNamespace('ChangeTests', realpath(__DIR__) .'/');
$zendLoader->register();
