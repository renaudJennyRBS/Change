<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	public function setAuthorization($event, $role = null, $resource = null, $privilege = null)
	{
		$authorisation = function(Event $event) use ($role, $resource, $privilege)
		{
			return $event->getPermissionsManager()->isAllowed($role, $resource, $privilege);
		};
		$event->setAuthorization($authorisation);
	}
}