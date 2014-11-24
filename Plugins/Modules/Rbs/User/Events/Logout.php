<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Events;

use Change\Events\Event;

/**
 * @name \Rbs\User\Events\Logout
 */
class Logout
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$authenticationManager = $applicationServices->getAuthenticationManager();
		$authenticationManager->setCurrentUser(null);
		$authenticationManager->setConfirmed(false);
	}
}