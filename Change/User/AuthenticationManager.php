<?php
namespace Change\User;

/**
* @name \Change\User\AuthenticationManager
*/
class AuthenticationManager
{

	const EVENT_MANAGER_IDENTIFIER = 'AuthenticationManager';

	const EVENT_LOGIN = 'login';

	/**
	 * @var \Change\Events\SharedEventManager
	 */
	protected $sharedEventManager;

	/**
	 * @var \Zend\EventManager\EventManager
	 */
	protected $eventManager;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var \Change\User\UserInterface|null
	 */
	protected $currentUser;

	/**
	 * @param \Change\User\UserInterface $currentUser
	 */
	public function setCurrentUser(\Change\User\UserInterface $currentUser = null)
	{
		$this->currentUser = $currentUser;
	}

	/**
	 * @return \Change\User\UserInterface|null
	 */
	public function getCurrentUser()
	{
		return $this->currentUser;
	}

	/**
	 * @param \Change\Events\SharedEventManager $sharedEventManager
	 */
	public function setSharedEventManager(\Change\Events\SharedEventManager $sharedEventManager)
	{
		$this->sharedEventManager = $sharedEventManager;
	}

	/**
	 * @return \Change\Events\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		return $this->sharedEventManager;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return \Zend\EventManager\EventManager
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$this->eventManager = new \Zend\EventManager\EventManager(static::EVENT_MANAGER_IDENTIFIER);
			$this->eventManager->setSharedManager($this->getSharedEventManager());
		}
		return $this->eventManager;
	}

	/**
	 * @param string $login
	 * @param string $password
	 * @param string $realm
	 * @return UserInterface|null
	 */
	public function login($login, $password, $realm)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('login' => $login,
			'password' => $password, 'realm' => $realm));
		$args['documentServices'] = $this->getDocumentServices();

		$event = new \Zend\EventManager\Event(static::EVENT_LOGIN, $this, $args);
		$this->getEventManager()->trigger($event);

		$user = $event->getParam('user');
		if ($user instanceof UserInterface)
		{
			return $user;
		}
		return null;
	}
}