<?php
namespace Change\Mvc;

/**
 * @name \Change\Mvc\Storage
 */
class Storage
{
	/**
	 * @var \Change\Mvc\Context
	 */
	private $context = null;
	
	/**
	 * @var \Zend\Session\Container 
	 */
	private $changeSessionContainer;
	
	/**
	 * @var \Zend\Session\Container  
	 */
	private $backuserSessionContainer;
	
	/**
	 * @var \Zend\Session\Container  
	 */
	private $frontuserSessionContainer;
	
	/**
	 * @var array
	 */
	protected $parameters = array('session_name' => 'CHANGESESSIONID', 'auto_start' => true);
	
	/**
	 * @var boolean
	 */
	private $started = null;
	
	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;
	
	/**
	 * @param \Change\Logging\Logging $logging
	 */
	public function __construct(\Change\Logging\Logging $logging)
	{
		$this->logging = $logging;
	}
	
	/**
	 * @param \Change\Mvc\Context $context
	 * @param array $parameters
	 */
	public function initialize($context, $parameters = null)
	{
		$this->context = $context;
		if ($parameters != null)
		{
			$this->parameters = array_merge($this->parameters, $parameters);
		}	
	
		if (!$this->parameters['auto_start'])
		{
			$this->startSession();
		}
	}
	
	/**
	 * Start session.
	 */
	protected function startSession()
	{
		if (isset($_SERVER["SERVER_ADDR"]))
		{
			$sessionManager = new \Zend\Session\SessionManager();
			$sessionManager->setName($this->parameters['session_name']);
			
			\Zend\Session\Container::setDefaultManager($sessionManager);

			$this->changeSessionContainer = new \Zend\Session\Container('GLOBAL');
			$this->backuserSessionContainer = new \Zend\Session\Container('BACKOFFICE');
			$this->frontuserSessionContainer = new \Zend\Session\Container('FRONTOFFICE');
			$this->started = true;
			$this->logging->registerSessionId($sessionManager->getId());
	
			$currentKey =  $this->getSecureKey();
			$md5 = $this->read('framework_SecureKey');
			if ($md5 === null)
			{
				$this->write('framework_SecureKey', $currentKey);
				$this->write('framework_SecurePort', $_SERVER["SERVER_PORT"]);
			} 
			else if ($md5 !== $currentKey)
			{
				$oldSessionId = $sessionManager->getId();
				$sessionManager->regenerateId(true);
				$this->logging->registerSessionId($sessionManager->getId());
				$this->sessionIdChanged($oldSessionId);
			}
			else if ($this->read('framework_SecurePort') !== $_SERVER["SERVER_PORT"])
			{
				$oldSessionId = $sessionManager->getId();
				$sessionManager->regenerateId(false);
				$this->write('framework_SecurePort', $_SERVER["SERVER_PORT"]);
				$this->logging->registerSessionId($sessionManager->getId());
				$this->sessionIdChanged($oldSessionId);
			}
		}
		else
		{
			$this->started = false;
		}
	}
	
	/**
	 * Close session.
	 */
	protected function stopSession()
	{
		if ($this->started === true)
		{
			$this->started = null;
			\Zend\Session\Container::getDefaultManager()->writeClose();
		}
	}

	/**
	 * @return string
	 */
	protected function getSecureKey()
	{
		$string = 'CHANGEKEY';
		if (defined('SECURE_SESSION_BY_IP') &&  SECURE_SESSION_BY_IP)
		{
			$string .= $_SERVER['REMOTE_ADDR'];
		}
		return md5($string);
	}
	
	/**
	 * @param string $oldSessionId 
	 */
	protected function sessionIdChanged($oldSessionId)
	{

	}

	/**
	 * @return \Change\Mvc\Context
	 */
	public function getContext()
	{
		return $this->context;
	}
	
	/**
	 * @param string $key
	 * @param \Zend\Session\Container $container
	 * @return Mixed 
	 */
	public function &readForContainer($key, $container)
	{
		if ($this->started === null) {$this->startSession();}
		$retval = null;
		if ($this->started && isset($container[$key]))
		{
			$retval =  $container[$key];
		}
		return $retval;
	}
	
	/**
	 * @param string $key
	 * @return string 
	 */
	public function &read($key)
	{
		return $this->readForContainer($key, $this->getChangeSessionContainer());
	}
	
	/**
	 * @param string $key
	 * @return string 
	 */
	public function &readForUser($key)
	{
		return $this->readForContainer($key, $this->getUserSessionContainer());
	}
	
	/**
	 * 
	 * @param string $key
	 * @param \Zend\Session\Container $container
	 * @return mixed
	 */
	public function removeForContainer($key, $container)
	{
		if ($this->started === null) {$this->startSession();}
		$retval = null;
		if ($this->started && isset($container[$key]))
		{
			$retval = $container[$key];
			unset($container[$key]);
		}
		return $retval;
	}
	
	/**
	 * @param string $key
	 * @return Mixed
	 */
	public function remove($key)
	{
		return $this->removeForContainer($key, $this->getChangeSessionContainer());
	}
	
	/**
	 * @param string $key
	 * @return Mixed
	 */
	public function removeForUser($key)
	{
		return $this->removeForContainer($key, $this->getUserSessionContainer());
	}

	/**
	 * @param string $key
	 * @param Mixed $data
	 * @param \Zend\Session\Container $container 
	 */
	public function writeForContainer($key, &$data, $container)
	{
		if ($this->started === null) {$this->startSession();}
		if ($this->started)
		{
			$container->$key = $data;
		}
	}
	
	/**
	 * @param string $key
	 * @param Mixed $data
	 */
	public function write($key, &$data)
	{
		$this->writeForContainer($key, $data, $this->getChangeSessionContainer());
	}
	
	/**
	 * @param string $key
	 * @param Mixed $data
	 */
	public function writeForUser($key, &$data)
	{
		$this->writeForContainer($key, $data, $this->getUserSessionContainer());
	}
	
	/**
	 * @param \Zend\Session\Container $container
	 * @return Array 
	 */
	public function readAllForContainer($container)
	{
		if ($this->started === null) {$this->startSession();}
		if ($this->started)
		{
			return $container->getIterator()->getArrayCopy();
		}
		return array();
	}
	
	/**
	 * @return array 
	 */
	public function readAll()
	{
		return $this->readAllForContainer($this->getChangeSessionContainer());
	}
	
	/**
	 * @return array 
	 */
	public function readAllForUser()
	{
		return $this->readAllForContainer($this->getUserSessionContainer());
	}
	
	/**
	 * Clear the gobal session.
	 */
	public function clear()
	{
		$container = $this->getChangeSessionContainer();
		if ($this->started) 
		{
			foreach ($container as $key => $value)
			{
				unset($container[$key]);
			}
		}
	}
	
	/**
	 * Clear the user session.
	 */
	public function clearForUser()
	{
		$container = $this->getUserSessionContainer();
		if ($this->started) 
		{
			foreach (array_keys($container->getArrayCopy()) as $key)
			{
				unset($container[$key]);
			}
		}
	}
	
	/**
	 */
	public function shutdown()
	{
		$this->stopSession();
	}
	
	/**
	 * This method returns the \Zend\Session\Container instance used to store related session data
	 * 
	 * @return \Zend\Session\Container  
	 */
	public function getChangeSessionContainer()
	{
		if ($this->started === null) {$this->startSession();}
		return $this->changeSessionContainer;
	}
	
	/**
	 * This method returns the \Zend\Session\Container instance used to store session data whose scope is authentified navigation only (ie: gets cleaned when authentified user disconnects)
	 * 
	 * @return \Zend\Session\Container 
	 */
	public function getUserSessionContainer()
	{
		if ($this->context->getUser()->getUserNamespace() === User::BACKEND_NAMESPACE)
		{
			return $this->getBackofficeSessionContainer();
		}
		return $this->getFontofficeSessionContainer();
	}
	
	/**
	 * @return \Zend\Session\Container 
	 */
	public function getBackofficeSessionContainer()
	{
		if ($this->started === null) {$this->startSession();}
		return $this->backuserSessionContainer;	
	}
	
	/**
	 * @return \Zend\Session\Container 
	 */
	public function getFontofficeSessionContainer()
	{
		if ($this->started === null) {$this->startSession();}
		return $this->frontuserSessionContainer;
	}
}