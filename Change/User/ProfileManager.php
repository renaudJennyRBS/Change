<?php
namespace Change\User;

use Change\Documents\DocumentServices;

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
	 * @var DocumentServices
	 */
	protected $documentServices;

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
			return $config->getEntry('Change/Events/ProfileManager', array());
		}
		return array();
	}

	/**
	 * @param $user
	 * @return string[]
	 */
	public function getProfileNames()
	{
		$event = new \Zend\EventManager\Event(static::EVENT_PROFILES, $this);
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
		$args = $em->prepareArgs(array('user' => $user, 'profileName' => $profileName, 'documentServices' => $this->getDocumentServices()));
		$event = new \Zend\EventManager\Event(static::EVENT_LOAD, $this, $args);
		$this->getEventManager()->trigger($event);
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
	 * @param ProfileInterface $profile
	 * @return ProfileInterface|null
	 */
	public function saveProfile($user, $profile)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('user' => $user, 'profile' => $profile, 'documentServices' => $this->getDocumentServices()));
		$event = new \Zend\EventManager\Event(static::EVENT_SAVE, $this, $args);
		$this->getEventManager()->trigger($event);

		$profile = $event->getParam('profile');
		if ($profile instanceof ProfileInterface)
		{
			return $profile;
		}
		return null;
	}
}