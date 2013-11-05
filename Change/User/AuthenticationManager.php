<?php
namespace Change\User;

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
	 * @var UserInterface|null
	 */
	protected $currentUser;

	/**
	 * @var \Change\Configuration\Configuration;
	 */
	protected $configuration;

	/**
	 * @param \Change\Configuration\Configuration $configuration
	 * @return $this
	 */
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
		return $this;
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->configuration;
	}

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
		return $this->getConfiguration()->getEntry('Change/Events/AuthenticationManager', array());
	}

	/**
	 * @param integer $userId
	 * @return UserInterface|null
	 */
	public function getById($userId)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('userId' => $userId));
		$event = new \Change\Events\Event(static::EVENT_BY_USER_ID, $this, $args);
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

		$event = new \Change\Events\Event(static::EVENT_LOGIN, $this, $args);
		$this->getEventManager()->trigger($event);
		$user = $event->getParam('user');
		if ($user instanceof UserInterface)
		{
			return $user;
		}
		return null;
	}
}