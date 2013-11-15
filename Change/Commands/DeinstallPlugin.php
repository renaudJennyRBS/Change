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
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$type = $event->getParam('type');
		$vendor = $event->getParam('vendor');
		$shortName = $event->getParam('name');

		$response = $event->getCommandResponse();

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
				$response->addErrorMessage("Error deinstall plugin");
				$applicationServices->getLogging()->exception($e);
				throw $e;
			}
		}
		else
		{
			$response->addInfoMessage('Nothing to do');
		}
	}
}