<?php
namespace Change\Mvc;

/**
 * @name \Change\Mvc\Controller
 */
class Controller
{
	/**
	 * @var \Change\Mvc\Context
	 */
	protected $context = null;
	
	/**
	 * @var integer
	 */
	protected $maxActionStackSize = 100;
	
	/**
	 * @var \Change\Mvc\ActionStack
	 */
	protected $actionStack;
	
	/**
	 * @var \Zend\Uri\UriInterface
	 */
	protected $uri;
	
	/**
	 * @var \Change\Application
	 */
	protected $application;
	
	/**
	 * @param \Change\Application $application
	 */
	public function __construct(\Change\Application $application)
	{
		$this->application = $application;
		$this->initialize();
	}

	/**
	 * @return void
	 */
	protected function initialize()
	{
		$this->actionStack = new ActionStack();
		$this->loadContext();
		$this->initialiseUri();
		register_shutdown_function(array($this, 'shutdown'));
	}
	
	/**
	 */
	protected function initialiseUri()
	{
		$uri = new \Zend\Uri\Http();
		if (isset($_SERVER['HTTP_HOST']))
		{
			$configuration = \Change\Application::getInstance()->getConfiguration();
			$httpsMarker = $configuration->getEntry('general/https-request-marker', 'HTTPS');
			$httpsMarkerValue = $configuration->getEntry('general/https-request-marker-value', 'on');
			
			$scheme = (isset($_SERVER[$httpsMarker]) && ($_SERVER[$httpsMarker] === $httpsMarkerValue)) ? 'https' : 'http';
			$uri->setScheme($scheme);
			$uri->setHost($_SERVER['HTTP_HOST']);
		}
		if (isset($_SERVER['SERVER_PORT']))
		{
			$uri->setPort($_SERVER['SERVER_PORT']);
		}
		
		if (isset($_SERVER['REQUEST_URI']))
		{
			$string = $_SERVER['REQUEST_URI'];
			$pos = strpos($string, '?');
			if ($pos === false)
			{
				$uri->setPath($string);
			}
			elseif ($pos === 0)
			{
				$uri->setQuery(substr($string, $pos + 1));
			}
			else
			{
				$uri->setPath(substr($string, 0, $pos));
				$uri->setQuery(substr($string, $pos + 1));
			}
		}
		$uri->normalize();
		$this->uri = $uri;
	}
	
	/**
	 */
	protected function loadContext()
	{
		$this->context = new Context($this);
	}
	
	/**
	 * @return void
	 */
	public function shutdown()
	{
		if ($this->context)
		{
			$this->context->shutdown();
			$this->actionStack = null;
			$this->context = null;
		}
	}
	
	/**
	 * @return \Change\Mvc\ActionStack
	 */
	public function getActionStack()
	{
		return $this->actionStack;
	}
	
	/**
	 * @return \Change\Mvc\AbstractAction
	 */
	public function getCurrentAction()
	{
		return $this->actionStack->getLastEntry();
	}
	
	/**
	 */
	public function dispatch()
	{
		$moduleName = $this->getRequest()->getParameter('module');
		if ($moduleName == null)
		{
			$moduleName = 'website';
		}
		
		$actionName = $this->getRequest()->getParameter('action');
		if ($actionName == null)
		{
			// no action has been specified
			if ($this->actionExists($moduleName, 'Index'))
			{
				 // an Index action exists
			   	$actionName = 'Index';
			}
			else
			{
				$moduleName =  'website';
				$actionName = 'Error404';
			}
		}
		// Make the first request.
		$this->forward($moduleName, $actionName);
	}
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @return string|null
	 */
	protected function getActionClassName($moduleName, $actionName)
	{
		$className = '\Change\\' . ucfirst($moduleName) . '\Actions\\' . ucfirst($actionName);
		return class_exists($className) ? $className : null;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 */
	public function actionExists($moduleName, $actionName)
	{
		return $this->getActionClassName($moduleName, $actionName) !== null;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @return \Change\Mvc\AbstractAction
	 */
	protected function getAction($moduleName, $actionName)
	{
		$actionClassName = $this->getActionClassName($moduleName, $actionName);
		if ($actionClassName === null)
		{
			throw new \Exception('Action '. $moduleName .'/' . $actionName . ' not found');
		}
		
		$action = new $actionClassName();
		if ($action instanceof AbstractAction)
		{
			$action->setFullName($moduleName, $actionName);
			return $action;
		}
		
		throw new \Exception('Invalid "'. get_class($action) .'" action type, "\Change\Mvc\AbstractAction" expected');
	}

	/**
	 * @param string $moduleName
	 * @param string $viewName
	 * @return string|null
	 */
	protected function getViewClassName($moduleName, $viewName)
	{
		$className = '\Change\\' . ucfirst($moduleName) . '\Views\\' . ucfirst($viewName);
		return class_exists($className) ? $className : null;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $viewName
	 * @return boolean
	 */
	public function viewExists($moduleName, $viewName)
	{
		return $this->getViewClassName($moduleName, $viewName) !== null;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $viewName
	 * @return \Change\Mvc\AbstractView
	 */
	protected function getView($moduleName, $viewName)
	{
		$viewClassName = $this->getViewClassName($moduleName, $viewName);
		if ($viewClassName === null)
		{
			throw new \Exception('View '. $moduleName .'/' . $viewName . ' not found');
		}
		$view = new $viewClassName();
		if ($view instanceof AbstractView)
		{
			return $view;
		}
		throw new \Exception('Invalid "'. get_class($view) .'" view type, "\Change\Mvc\AbstractView" expected');
	}
	
		
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @throws Exception
	 * @return void
	 */
	public function forward($moduleName, $actionName)
	{	
		if ($this->actionStack->getSize() > $this->maxActionStackSize)
		{
			throw new \Exception('Too many forwards have been detected for this request');
		}
	
		if ($this->actionExists($moduleName, $actionName))
		{
			$actionInstance = $this->getAction($moduleName, $actionName);
		}
		else
		{
			return $this->forward('website', 'Error404');
		}
		
		// Initialize the action.
		if ($actionInstance->initialize($this->context))
		{	
			// Create a new filter chain.
			if ($actionInstance->isSecure())
			{
				if (!$this->getUser()->isAuthenticated())
				{
					return $this->forward('users', 'Login');
				}
			}
			$this->actionStack->addEntry($actionInstance);
			
			$method = $this->getRequest()->getMethod();
			if (($actionInstance->getRequestMethods() & $method) != $method)
			{
				$viewName = $actionInstance->getDefaultView();
			} 
			else
			{
				if ($actionInstance->validate())
				{
					$viewName = $actionInstance->execute();
				} 
				else
				{
					$viewName = $actionInstance->handleError();
				}
			}

			if ($viewName != AbstractView::NONE)
			{
				if (is_array($viewName))
				{
					list ($moduleName, $viewName) = $viewName;
				}
				else
				{
					$viewName = $actionName . $viewName;
				}
	
				// display this view
				if (!$this->viewExists($moduleName, $viewName))
				{
					throw new \Exception('Module "'.$moduleName .'" does not contain the view "'. $viewName. 'View"');
				}
	
				// get the view instance
				$viewInstance = $this->getView($moduleName, $viewName);
	
				// initialize the view
				if ($viewInstance->initialize($this->context))
				{
	
					// view initialization completed successfully
					$viewInstance->execute();
					$viewInstance->render();
				} 
				else
				{
					throw new \Exception('View initialization failed for module "'.$moduleName .'", view '. $viewName. 'View"');
				}
			}
			
			$this->actionStack->popEntry();
		}
		
		$actionInstance->shutdown();
	}
	
	/**
	 * @return \Change\Mvc\Context
	 */
	public function getContext()
	{
		return $this->context;
	}
	
	/**
	 * @return \Change\Mvc\Storage
	 */
	public function getStorage()
	{
		return $this->getContext()->getStorage();
	}
	
	/**
	 * @return \Change\Mvc\Request
	 */
	public function getRequest()
	{
		return $this->getContext()->getRequest();
	}
	
	/**
	 * @return \Change\Mvc\User
	 */
	public function getUser()
	{
		return $this->getContext()->getUser();
	}
	
	/**
	 * @return string
	 */
	public function getUserAgentLanguage()
	{
		$lang = null;
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$lang = preg_split('/[,;]+/', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$lang = strtolower(substr(trim($lang[0]), 0, 2));
		}
		return $lang;
	}
	
	/**
	 * @return boolean
	 */
	public function addNoCacheHeader()
	{
		if (headers_sent())
		{
			return false;
		}
	
		header('Last-Modified: '. gmdate('D, d M Y H:i:s') . ' GMT');
		header('Expires: '. 'Mon, 26 Jul 1997 05:00:00 GMT');
		header('Cache-Control: '. 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		header('Pragma: '. 'no-cache');
		if (defined('NODE_NAME'))
		{
			header('X-Change-WebNode: '. NODE_NAME);
		}
		return true;
	}
	
	/**
	 * @return \Zend\Uri\UriInterface
	 */
	public function getUri()
	{
		return $this->uri;
	}
	
	/**
	 * @return string
	 */
	public function getClientIp()
	{
		$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null;
		$remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
		if (!empty($ip) && !empty($remoteAddr))
		{
			$ip .= ', ';
		}
		$ip .= $remoteAddr;
		return $ip;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @param array $parameters
	 */
	public function redirect($moduleName, $actionName, $parameters = null)
	{
		$request = $this->getRequest();	
		$requestParametersNames = $request->getParameterNames();
		if (is_array($parameters))
		{
			$urlParam = $parameters;
		}
		else
		{
			$urlParam = array();
			foreach ($requestParametersNames as $requestParameterName)
			{
				if (is_string($request->getParameter($requestParameterName)))
				{
					$urlParam[$requestParameterName] = $request->getParameter($requestParameterName);
				}
			}
		}
		
		$urlParam['module'] = $moduleName;
		$urlParam['action'] = $actionName;
		
		$url = $this->generateRedirectUrl($urlParam);
		$this->redirectToUrl($url);
	}
	
	/**
	 * @param string $url
	 * @param integer $delay
	 */
	public function redirectToUrl($url, $delay = 0)
	{
		if (!headers_sent())
		{
			header('Location: ' . $url);
		}
		echo '<html><head><meta http-equiv="refresh" content="',$delay,';url=', $url, '"/></head></html>';
		exit(0);
	}
	
	/**
	 * @param array $urlParams
	 */
	protected function generateRedirectUrl($urlParams)
	{
		//TODO Old class Usage
		return \LinkHelper::getParametrizedLink($urlParams)->getUrl();
	}
}