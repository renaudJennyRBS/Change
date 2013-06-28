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

		$path = realpath($event->getParam('path'));
		if (!$path)
		{
			$event->addErrorMessage('Path: "' . $event->getParam('path') . '" not found');
			return;
		}
		if (!is_writable($path))
		{
			$event->addErrorMessage('Path: "' . $path . '" is not writable');
			return;
		}

		$cmd = new \Change\Http\InitHttpFiles($application);
		$cmd->initializeControllers($path);
		$event->addInfoMessage('Document root path: "' . $path . '" is now set.');

	}
}