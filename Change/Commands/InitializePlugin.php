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
	 * @throws \Exception
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
			$applicationServices->getTransactionManager()->begin();

			$path = $applicationServices->getPluginManager()->initializePlugin($type, $vendor, $name, $package);
			$event->addInfoMessage('Plugin skeleton generated at ' . $path);
			
			$applicationServices->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$event->addErrorMessage($e->getMessage());
			throw $applicationServices->getTransactionManager()->rollback($e);
		}
	}
}