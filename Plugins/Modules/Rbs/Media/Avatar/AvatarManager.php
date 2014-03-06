<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Media\Avatar;

/**
 * @name \Rbs\Media\Avatar\AvatarManager
 * @api
 */
class AvatarManager implements \Zend\EventManager\EventsCapableInterface
{

	use \Change\Events\EventsCapableTrait;

	const AVATAR_MANAGER_IDENTIFIER = 'RbsMediaAvatarManager';
	const AVATAR_GET_AVATAR_URL = 'getAvatarUrl';

	/**
	 * @var \Change\Http\UrlManager
	 */
	protected $urlManager;

	/**
	 * @api
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
	}

	/**
	 * @api
	 * @return \Change\Http\UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @api
	 * Get the Avatar URL
	 * @param integer $size
	 * @param string $email
	 * @param \Rbs\User\Documents\User $user
	 * @param mixed[] $params
	 * @throws \RuntimeException
	 * @return null|string
	 */
	public function getAvatarUrl($size, $email, $user = null, array $params = null)
	{
		if (! $this->urlManager instanceof \Change\Http\UrlManager)
		{
			throw new \RuntimeException('UrlManager must be defined', 999999);
		}

		if ($this->urlManager->getSelf()->getScheme() == 'https')
		{
			$params['secure'] = true;
		}
		else
		{
			$params['secure'] = false;
		}

		$em = $this->getEventManager();
		$args = $em->prepareArgs($params);

		$args['email'] = $email;
		$args['user'] = $user;
		$args['size'] = $size;

		$event = new \Change\Events\Event(static::AVATAR_GET_AVATAR_URL, $this, $args);
		$this->getEventManager()->trigger($event);

		return $event->getParam('url');
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::AVATAR_MANAGER_IDENTIFIER;
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::AVATAR_GET_AVATAR_URL, array($this, 'onDefaultGetGravatarUrl'), 5);
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Media/Events/AvatarManager');
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetGravatarUrl(\Change\Events\Event $event)
	{
		$url = null;

		/** @var \Rbs\User\Documents\User $user */
		$user = $event->getParam('user');
		if ($user !== null)
		{
			$email = $user->getEmail();
		}
		else
		{
			$email = $event->getParam('email');
		}

		if (!\Change\Stdlib\String::isEmpty($email))
		{
			$avatar = new Gravatar($email);

			if (!\Change\Stdlib\String::isEmpty($event->getParam('defaultImg')))
			{
				$avatar->setDefaultImg($event->getParam('defaultImg'));
			}

			if (!\Change\Stdlib\String::isEmpty($event->getParam('rating')))
			{
				$avatar->setRating($event->getParam('rating'));
			}

			if (!\Change\Stdlib\String::isEmpty($event->getParam('size')))
			{
				$avatar->setSize($event->getParam('size'));
			}

			if (!\Change\Stdlib\String::isEmpty($event->getParam('secure')))
			{
				$avatar->setSecure((bool)$event->getParam('secure'));
			}

			$url = $avatar->getUrl();
		}

		$event->setParam('url', $url);
	}
}