<?php
namespace Change\User;

use Change\Documents\DocumentServices;

/**
* @name \Change\User\AuthenticationManager
*/
class AuthenticationManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'AuthenticationManager';

	const EVENT_LOGIN = 'login';

	const EVENT_BY_USER_ID = 'byUserId';

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var UserInterface|null
	 */
	protected $currentUser;

	/**
	 * @param UserInterface $currentUser
	 */
	public function setCurrentUser($currentUser = null)
	{
		$this->currentUser = $currentUser;
	}

	/**
	 * @return UserInterface
	 */
	public function getCurrentUser()
	{
		return $this->currentUser === null ? new AnonymousUser() : $this->currentUser;
	}

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
		if ($documentServices !== null  && $this->sharedEventManager === null)
		{
			$this->setSharedEventManager($documentServices->getApplicationServices()->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->documentServices)
		{
			$config = $this->documentServices->getApplicationServices()->getApplication()->getConfiguration();
			return $config->getEntry('Change/Events/AuthenticationManager', array());
		}
		return array();
	}

	/**
	 * @param integer $userId
	 * @return UserInterface|null
	 */
	public function getById($userId)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('userId' => $userId));
		$args['documentServices'] = $this->getDocumentServices();
		$event = new \Zend\EventManager\Event(static::EVENT_BY_USER_ID, $this, $args);
		$this->getEventManager()->trigger($event);
		$user = $event->getParam('user');
		if ($user instanceof UserInterface)
		{
			return $user;
		}
		return null;
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