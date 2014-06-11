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
* @name \Change\User\ProfileManager
*/
class ProfileManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'ProfileManager';

	const EVENT_LOAD = 'load';

	const EVENT_PROFILES = 'profiles';

	const EVENT_SAVE = 'save';

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
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/ProfileManager');
	}

	/**
	 * @return string[]
	 */
	public function getProfileNames()
	{
		$event = new \Change\Events\Event(static::EVENT_PROFILES, $this);
		$this->getEventManager()->trigger($event);
		$profiles = $event->getParam('profiles');
		if (is_array($profiles))
		{
			return $profiles;
		}
		return [];
	}

	/**
	 * @param UserInterface $user
	 * @param string $profileName
	 * @return ProfileInterface|null
	 */
	public function loadProfile($user, $profileName)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('user' => $user, 'profileName' => $profileName));
		$event = new \Change\Events\Event(static::EVENT_LOAD, $this, $args);
		$em->trigger($event);

		$profile = $event->getParam('profile');
		if ($profile instanceof ProfileInterface)
		{
			return $profile;
		}
		return null;
	}

	/**
	 * @param UserInterface $user
	 * @param ProfileInterface $profile
	 * @return ProfileInterface|null
	 */
	public function saveProfile($user, $profile)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('user' => $user, 'profile' => $profile));
		$event = new \Change\Events\Event(static::EVENT_SAVE, $this, $args);
		$em->trigger($event);

		$profile = $event->getParam('profile');
		if ($profile instanceof ProfileInterface)
		{
			return $profile;
		}
		return null;
	}
}