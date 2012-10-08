<?php

namespace Change;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'AbstractSingleton.php';

/**
 *
 * @method \Change\Application getInstance()
 */
class Application extends AbstractSingleton
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
	/**
	 * Injection-base autoload if you want injection to work, this should be the
	 * last autoload coming from RBS Change you should register
	 * (it gets prepended to the autoload stack).
	 */
	public function registerInjectionAutoload()
	{
		$basePath = \Change\Stdlib\Path::compilationPath('Injection');
		spl_autoload_register(function ($className) use($basePath)
		{
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
		$namespaces = array('Change' => PROJECT_HOME . DIRECTORY_SEPARATOR . 'Change', 
			'Zend' => PROJECT_HOME . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'zendframework' . DIRECTORY_SEPARATOR . 'zendframework' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'Zend', 
			'ZendOAuth' => PROJECT_HOME . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . 'zendframework' . DIRECTORY_SEPARATOR . 'zendoauth' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'ZendOAuth');
		
		require_once $namespaces['Zend'] . DIRECTORY_SEPARATOR . 'Loader' . DIRECTORY_SEPARATOR . 'StandardAutoloader.php';
		foreach ($namespaces as $namespace => $path)
		{
			$zendLoader = new \Zend\Loader\StandardAutoloader();
			$zendLoader->registerNamespace($namespace, $path);
			$zendLoader->register();
		}
	}
	
	/**
	 *
	 * @var \Change\Mvc\Controller
	 */
	protected $controller;
	
	/**
	 *
	 * @return \Change\Mvc\Controller
	 */
	public function getController()
	{
		return $this->controller;
	}
	
	/**
	 *
	 * @return \Change\Mvc\Controller
	 */
	public function setController($controller)
	{
		$this->controller = $controller;
	}
	
	/**
	 *
	 * @return \Change\Configuration\Configuration
	 */
	public function getConfiguration()
	{
		return $this->getApplicationServices()->getConfiguration();
	}
	
	/**
	 *
	 * @param \Change\Configuration\Configuration $configuration        	
	 */
	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
	}
	
	/**
	 *
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
	 * Set the application services DiC 
	 * 
	 * @param \Change\Application\ApplicationServices $applicationServices        	
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}
	
	/**
	 *
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		if (!$this->applicationServices)
		{
			$this->setApplicationServices($this->defaultApplicationServices());
		}
		return $this->applicationServices;
	}
	
	/**
	 *
	 * @return \Change\Application\ApplicationServices
	 */
	public function defaultApplicationServices()
	{
		$applicationServices = new \Change\Application\ApplicationServices();
		$im = $applicationServices->instanceManager();
		$applicationServices->configure(new \Zend\Di\Config(array(
			'definition' => array(
				'class' => array('Change\Configuration\Configuration' => array(), 
					'Change\I18n\I18nManager' => array('__construct' => array(
						'config' => array('type' => 'Change\Configuration\Configuration', 'required' => true),
						'dbProvider' => array('type' => 'Change\Db\DbProvider', 'required' => true))), 
					'Change\Db\DbProvider' => array(
						'newInstance' => array(
							'config' => array('type' => 'Change\Configuration\Configuration', 'required' => true),
							'logging' => array('type' => 'Change\Logging\Logging', 'required' => true),
							), 
						'instantiator' => array('Change\Db\DbProvider', 'newInstance')),
					'Change\Logging\Logging' => array('__construct' => array(
						'config' => array('type' => 'Change\Configuration\Configuration', 'required' => true)))
					)
				), 
			
			'instance' => array(
				'Change\Configuration\Configuration' => array(
					'parameters' => array('compiledFile' => \Change\Stdlib\Path::compilationPath('Config', 'project.php'))), 
				'Change\I18n\I18nManager' => array('injections' => array('Change\Configuration\Configuration', 'Change\Db\DbProvider')), 
				'Change\Db\DbProvider' => array('injections' => array('Change\Configuration\Configuration', 'Change\Logging\Logging')),
				'Change\Logging\Logging' => array('injections' => array('Change\Configuration\Configuration'))
			))));
		return $applicationServices;
	}
	
	/**
	 * Call this to start application!
	 */
	public function start()
	{
		$this->registerNamespaceAutoload();
		$bootStrapFilePath = \Change\Stdlib\Path::appPath('Bootstrap.php');
		if (file_exists($bootStrapFilePath))
		{
			require_once $bootStrapFilePath;
			if (class_exists('\App\Bootstrap', false))
			{
				\App\Bootstrap::main($this);
			}
		}
		if (self::inDevelopmentMode())
		{
			\Change\Injection\Service::getInstance()->update();
		}
		$this->registerInjectionAutoload();
	}
	
	/**
	 *
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