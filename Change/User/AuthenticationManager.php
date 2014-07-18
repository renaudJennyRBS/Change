<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\User;

/**
* @name \Change\User\AuthenticationManager
*/
class AuthenticationManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'AuthenticationManager';

	const EVENT_LOGIN = 'login';

	const EVENT_LOGOUT = 'logout';

	const EVENT_BY_USER_ID = 'byUserId';

	/**
	 * @var UserInterface|null
	 */
	protected $currentUser;

	/**
	 * @var boolean
	 */
	protected $confirmed = false;

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
	 * @param boolean $confirmed
	 * @return $this
	 */
	public function setConfirmed($confirmed)
	{
		$this->confirmed = $confirmed;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getConfirmed()
	{
		return $this->confirmed;
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
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/AuthenticationManager');
	}

	/**
	 * @api
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
	 * @api
	 * @param string $login
	 * @param string $password
	 * @param string $realm
	 * @param array|null $options
	 * @return UserInterface|null
	 */
	public function login($login, $password, $realm, $options = null)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('login' => $login,
			'password' => $password, 'realm' => $realm, 'options' => $options));

		$event = new \Change\Events\Event(static::EVENT_LOGIN, $this, $args);
		$this->getEventManager()->trigger($event);
		$user = $event->getParam('user');
		if ($user instanceof UserInterface)
		{
			return $user;
		}
		return null;
	}

	/**
	 * @api
	 * @param array|null $options
	 */
	public function logout($options = null)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['user' => $this->getCurrentUser(), 'options' => $options]);
		$this->getEventManager()->trigger(static::EVENT_LOGOUT, $this, $args);
		$this->setCurrentUser(null);
	}
}