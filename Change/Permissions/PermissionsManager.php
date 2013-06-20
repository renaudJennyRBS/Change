<?php
namespace Change\Permissions;

use Zend\Permissions\Acl\Resource;
use Zend\Permissions\Acl\Role;

/**
 * @name \Change\Permissions\PermissionsManager
 */
class PermissionsManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'PermissionsManager';

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var boolean
	 */
	protected $allow = false;

	/**
	 * @var \Change\User\UserInterface
	 */
	protected $user;

	/**
	 * @param \Change\User\UserInterface $user
	 */
	public function setUser(\Change\User\UserInterface $user = null)
	{
		$this->user = $user;
	}

	/**
	 * @return \Change\User\UserInterface
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		if ($this->sharedEventManager === null)
		{
			$this->setSharedEventManager($applicationServices->getApplication()->getSharedEventManager());
		}
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
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
		return array();
	}

	/**
	 * @param boolean $allow
	 * @return boolean
	 */
	public function allow($allow = null)
	{
		if (is_bool($allow))
		{
			$this->allow = $allow;
		}
		return $this->allow;
	}

	/**
	 * @param string $role
	 * @param string $resource
	 * @param string $privilege
	 * @return boolean
	 */
	public function isAllowed($role = null, $resource = null, $privilege = null)
	{
		//TODO Implementation needed
		$isAllowed = $this->getUser() !== null;
		$this->getApplicationServices()->getLogging()->info('isAllowed(' . var_export($role, true)
		. ', '. var_export($resource, true)
		. ', '. var_export($privilege, true).'): ' . var_export($isAllowed, true));

		return $isAllowed;
	}
}