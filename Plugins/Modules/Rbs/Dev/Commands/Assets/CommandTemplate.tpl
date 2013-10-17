<?php
namespace #namespace#;

use Change\Commands\Events\Event;

/**
 * @name \#namespace#\#className#
 */
class #className#
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		//Code of your command here

//		$applicationServices = new \Change\Application\ApplicationServices($application);

		$event->addInfoMessage('Done.');
	}
}