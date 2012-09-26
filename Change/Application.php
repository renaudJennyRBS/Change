<?php
namespace Change;

require_once __DIR__  . DIRECTORY_SEPARATOR . 'AbstractSingleton.php';

/**
 * @method \Change\Application getInstance()
 */
class Application extends AbstractSingleton
{
	public function registerAutoload()
	{
		$namespaces = array(
			'Change' => PROJECT_HOME  . DIRECTORY_SEPARATOR . 'Change' , 
			'Zend' => PROJECT_HOME  . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'ZendFramework' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'Zend',
			'ZendOAuth' => PROJECT_HOME  . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'ZendOAuth' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'ZendOAuth',
		);
		
		require_once $namespaces['Zend'] . DIRECTORY_SEPARATOR . 'Loader' . DIRECTORY_SEPARATOR . 'StandardAutoloader.php';
		
		foreach ($namespaces as $namespace => $path)
		{
			$zendLoader  = new \Zend\Loader\StandardAutoloader();
			$zendLoader->registerNamespace($namespace, $path);
			$zendLoader->register();
		}	
	}
}