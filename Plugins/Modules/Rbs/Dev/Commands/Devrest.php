<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Dev\Commands;

/**
 * @name \Rbs\Dev\Commands\Devrest
 */
class Devrest
{
	/**
	 * @param \Change\Commands\Events\Event $event
	 */
	public function execute(\Change\Commands\Events\Event $event)
	{
		$response = $event->getCommandResponse();

		$application = $event->getApplication();
		$webBaseDirectory = $application->getConfiguration('Change/Install/webBaseDirectory');
		$devRestPath = $application->getWorkspace()->composeAbsolutePath($webBaseDirectory,'devrest.php');
		$response->addInfoMessage('Path to devrest.php: ' . $devRestPath);
		if ($event->getParam('mode') === 'up')
		{
			$content = file_get_contents(__DIR__ . '/Assets/devrest.tpl');
			$content = str_replace('#projectPath#', var_export($application->getWorkspace()->projectPath(), true), $content);
			\Change\Stdlib\File::write($devRestPath, $content);
			$response->addInfoMessage('devrest.php successfully enabled.');
		}
		else
		{
			if (file_exists($devRestPath))
			{
				unlink($devRestPath);
				$response->addInfoMessage('devrest.php successfully disabled.');
			}
			else
			{
				$response->addInfoMessage('devrest.php was already disabled.');
			}
		}
	}
}