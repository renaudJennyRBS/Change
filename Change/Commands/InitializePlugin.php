<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\InitializePlugin
 */
class InitializePlugin
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$applicationServices = new \Change\Application\ApplicationServices($application);

		$type = $event->getParam('type');
		$vendor = $event->getParam('vendor');
		$name = $event->getParam('name');
		$package = $event->getParam('package');

		try
		{
			$path = $applicationServices->getPluginManager()->initializePlugin($type, $vendor, $name, $package);
			$event->addInfoMessage('Plugin skeleton generated at ' . $path);
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$event->addErrorMessage($e->getMessage());
		}
	}
}