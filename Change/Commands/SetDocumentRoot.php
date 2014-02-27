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
 * @name \Change\Commands\SetDocumentRoot
 */
class SetDocumentRoot
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$webBaseDirectory = $event->getParam('webBaseDirectory');
		$webBaseURLPath = rtrim($event->getParam('webBaseURLPath'), '/');

		$response = $event->getCommandResponse();

		if ($webBaseDirectory === '.')
		{
			$webBaseDirectory = '';
		}
		$realPath = $application->getWorkspace()->composeAbsolutePath($webBaseDirectory);
		if (!is_dir($realPath))
		{
			$response->addErrorMessage('Path: "' . $realPath . '" not found');
			return;
		}
		if (!is_writable($realPath))
		{
			$response->addErrorMessage('Path: "' . $realPath . '" is not writable');
			return;
		}

		$cmd = new \Change\Http\InitHttpFiles($application);
		$cmd->initializeControllers($webBaseDirectory, $webBaseURLPath);
		$response->addInfoMessage('Web base Directory: "' . $webBaseDirectory . '" is now set.');
		$response->addInfoMessage('Web base URL Path: "' . $webBaseURLPath . '" is now set.');
	}
}