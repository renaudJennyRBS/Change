<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\DeinstallPlugin
 */
class DeinstallPlugin
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
		$shortName = $event->getParam('name');


		$pluginManager = $applicationServices->getPluginManager();

		$plugin = $pluginManager->getPlugin($type, $vendor, $shortName);
		if ($plugin && $plugin->getRegistrationDate() && $plugin->getConfigured())
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();
				$pluginManager->deinstall($plugin);
				$pluginManager->compile();
				$tm->commit();
			}
			catch(\Exception $e)
			{
				$event->addErrorMessage("Error deregistering plugin");
				$applicationServices->getLogging()->exception($e);
				throw $e;
			}
		}
		else
		{
			$event->addInfoMessage('Nothing to do');
		}
	}
}