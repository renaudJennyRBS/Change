<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace #namespace#;

/**
 * @name \#namespace#\#className#
 */
class #className#
{
	/**
	 * @param \Change\Commands\Events\Event $event
	 */
	public function execute(\Change\Commands\Events\Event $event)
	{
		$response = $event->getCommandResponse();

//		$application = $event->getApplication();
		// Code of your command here.

//		$applicationServices = $event->getApplicationServices();

		$response->addInfoMessage('Done.');
	}
}