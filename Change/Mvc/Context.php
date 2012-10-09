<?php
namespace Change\Mvc;

/**
 * @name \Change\Mvc\Context
 */
class Context
{
	/**
	 * The possible request modes : backoffice or frontoffice
	 */
	const BACKEND = 0;
	const FRONTEND = 1;
	
	/**
	 * @var integer
	 */
	protected $mode = self::FRONTEND;
	
	/**
	 * @var \Change\Mvc\Controller
	 */
	protected $controller = null;
	
	/**
	 * @var \Change\Mvc\Request
	 */
	protected $request = null;
	
	/**
	 * @var \Change\Mvc\Storage
	 */
	protected $storage = null;
	
	/**
	 * @var \Change\Mvc\User
	 */
	protected $user = null;
		
	/**
	 * @return integer
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * @param integer $mode
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}

	/**
	 * @param \Change\Mvc\Controller $controller
	 */
	public function __construct($controller) 
	{
		$this->initialize($controller);
	}
	
	/**
	 * @param \Change\Mvc\Controller $controller
	 */
	protected function initialize($controller)
	{
		if ($controller instanceof \Change\Mvc\Controller)
		{
			$this->controller = $controller;
			$this->request = new Request();
			$this->storage = new Storage(\Change\Application::getInstance()->getApplicationServices()->getLogging());
			$this->user = new User();
				
			$this->request->initialize($this);
			$this->storage->initialize($this);
			$this->user->initialize($this);
		}
	}
	
	public function shutdown()
	{
		$this->user->shutdown();
		$this->storage->shutdown();
		$this->request->shutdown();
		
		$this->user = null;
		$this->storage = null;
		$this->request = null;
		$this->controller = null;
	}
	
	/**
	 * @return string|null
	 */
	public function getActionName()
	{
		$action = $this->controller->getCurrentAction();
		return $action ? $action->getActionName() : null;
	}
	
	/**
	 * @return string|null
	 */
	public function getModuleName()
	{
		$action = $this->controller->getCurrentAction();
		return $action ? $action->getModuleName() : null;
	}

	/**
	 * @return \Change\Mvc\Controller
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * @return \Change\Mvc\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}
	
	/**
	 * @return \Change\Mvc\Storage
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	/**
	 * @return \Change\Mvc\User
	 */
	public function getUser()
	{
		return $this->user;
	}
}