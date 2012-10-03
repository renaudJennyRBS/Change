<?php
namespace Change;

require_once __DIR__  . DIRECTORY_SEPARATOR . 'AbstractSingleton.php';

/**
 * @method \Change\Application getInstance()
 */
class Application extends AbstractSingleton
{
	/**
	 * Register the different autoloads available in RBS Change
	 */
	public function registerAutoloads()
	{
		$this->registerNamespaceAutoload();
		// Remember that the injection-based autload always gets prepended to the autoload
		$this->registerInjectionAutoload();
	}
	
	/**
	 * Injection-base autoload if you want injection to work, this should be the last autoload coming from RBS Change you should register
	 * (it gets prepended to the autoload stack).
	 */
	public function registerInjectionAutoload()
	{
		$basePath = \Change\Stdlib\Path::compilationPath('Injection');
		spl_autoload_register(function($className) use ($basePath){
			$phpFileName = str_replace('\\', '_', $className) . '.php';
			$phpFilePath = $basePath . DIRECTORY_SEPARATOR . '_' . $phpFileName;
			if (file_exists($phpFilePath))
			{
				require_once $phpFilePath;
			}
		}, true, true);
	}
	
	/**
	 * Namespace-based autoloading
	 */
	public function registerNamespaceAutoload()
	{
		$namespaces = array(
			'Change' => PROJECT_HOME  . DIRECTORY_SEPARATOR . 'Change' ,
			'Zend' => PROJECT_HOME  . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'zendframework' . DIRECTORY_SEPARATOR . 'zendframework' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'Zend',
			'ZendOAuth' => PROJECT_HOME  . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'zendframework' . DIRECTORY_SEPARATOR . 'zendoauth' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'ZendOAuth',
		);
	
		require_once $namespaces['Zend'] . DIRECTORY_SEPARATOR . 'Loader' . DIRECTORY_SEPARATOR . 'StandardAutoloader.php';
		foreach ($namespaces as $namespace => $path)
			{
			$zendLoader  = new \Zend\Loader\StandardAutoloader();
			$zendLoader->registerNamespace($namespace, $path);
			$zendLoader->register();
		}
	}
	
	/**
	 * @var \Change\Mvc\Controller
	 */
	protected $controller;
	
	/**
	 * @return \Change\Mvc\Controller
	 */
	public function getController()
	{
		return $this->controller;
	}
		
	/**
	 * @return \Change\Mvc\Controller
	 */
	public function setController($controller)
	{
		$this->controller = $controller;
	}	
		
	/**
	 * @var \Change\Application\Configuration
	 */
	protected $configuration;
	
	/**
	 * Initialize the application.
	 */
	public function loadConfiguration()
	{
		$compiledFile = \Change\Stdlib\Path::compilationPath('Config', 'project.php');
		$this->setConfiguration(new \Change\Application\Configuration($compiledFile));
	}
	
	/**
	 * @return \Change\Application\Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}
	
	/**
	 * @param \Change\Application\Configuration $configuration
	 */
	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
	}
	
	/**
	 * @return string
	 */
	public function getProfile()
	{
		if (file_exists(PROJECT_HOME . '/profile'))
		{
			$profile = trim(file_get_contents(PROJECT_HOME . '/profile'));
		}
		else
		{
			$profile = 'default';
		}
		return $profile;
	}
	
	/**
	 * @see project config and DEVELOPMENT_MODE constant
	 * @return boolean
	 */
	public static function inDevelopmentMode()
	{
		if (!defined('DEVELOPMENT_MODE'))
		{
			// TODO old class
			\f_util_ProcessUtils::printBackTrace();
		}
		return DEVELOPMENT_MODE;
	}
}