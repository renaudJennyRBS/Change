<?php
namespace Rbs\Media\Avatar;

/**
 * @name \Rbs\Media\Avatar\AvatarManager
 * @api
 */
class AvatarManager implements \Zend\EventManager\EventsCapableInterface
{

	use \Change\Services\DefaultServicesTrait, \Change\Events\EventsCapableTrait  {
		\Change\Events\EventsCapableTrait::attachEvents as defaultAttachEvents;
	}

	const AVATAR_MANAGER_IDENTIFIER = 'RbsMediaAvatarManager';
	const AVATAR_GET_AVATAR_URL = 'getAvatarUrl';

	/**
	 * @var \Change\Http\UrlManager
	 */
	protected $urlManager;

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
		if ($applicationServices && $this->sharedEventManager === null)
		{
			$this->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
		}
	}

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

		$event = new \Zend\EventManager\Event(static::AVATAR_GET_AVATAR_URL, $this, $args);
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
	 * @param \Zend\EventManager\EventManager $eventManager
	 */
	protected function attachEvents(\Zend\EventManager\EventManager $eventManager)
	{
		$this->defaultAttachEvents($eventManager);
		$eventManager->attach(static::AVATAR_GET_AVATAR_URL, array($this, 'getGravatarUrl'), 5);
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		if ($this->applicationServices)
		{
			$config = $this->applicationServices->getApplication()->getConfiguration();
			return $config->getEntry('Rbs/Media/AvatarManager', array());
		}
		return array();
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function getGravatarUrl(\Zend\EventManager\Event $event)
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

			if (!\Change\Stdlib\String::isEmpty($event->getParam('imageSet')))
			{
				$avatar->setDefaultImg($event->getParam('imageSet'));
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