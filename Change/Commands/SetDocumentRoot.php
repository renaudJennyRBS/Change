<?php
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

		if ($webBaseDirectory === '.')
		{
			$webBaseDirectory = '';
		}
		$realPath = $application->getWorkspace()->composeAbsolutePath($webBaseDirectory);
		if (!is_dir($realPath))
		{
			$event->addErrorMessage('Path: "' . $realPath . '" not found');
			return;
		}
		if (!is_writable($realPath))
		{
			$event->addErrorMessage('Path: "' . $realPath . '" is not writable');
			return;
		}

		$cmd = new \Change\Http\InitHttpFiles($application);
		$cmd->initializeControllers($webBaseDirectory, $webBaseURLPath);
		$event->addInfoMessage('Web base Directory: "' . $webBaseDirectory . '" is now set.');
		$event->addInfoMessage('Web base URL Path: "' . $webBaseURLPath . '" is now set.');
	}
}