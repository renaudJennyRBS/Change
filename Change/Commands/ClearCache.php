<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\ClearCache
 */
class ClearCache
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		\Change\Stdlib\File::rmdir($application->getWorkspace()->cachePath(), true);

		$response = $event->getCommandResponse();
		$response->addInfoMessage('Done.');
	}
}