<?php
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