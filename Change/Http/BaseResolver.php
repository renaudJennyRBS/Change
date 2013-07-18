<?php
namespace Change\Http;

/**
 * @name \Change\Http\ActionResolver
 */
class BaseResolver
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function resolve($event)
	{
		$event->setAction(null);
	}

	/**
	 * @param Event $event
	 * @param string $role
	 * @param integer $resource
	 * @param string $privilege
	 */
	public function setAuthorisation($event, $role = null, $resource = null, $privilege = null)
	{
		$authorisation = function(Event $event) use ($role, $resource, $privilege)
		{
			return $event->getPermissionsManager()->isAllowed($role, $resource, $privilege);
		};
		$event->setAuthorization($authorisation);
	}
}