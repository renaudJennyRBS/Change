<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\DeregisterPlugin
 */
class DeregisterPlugin
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


		$pluginManager = $applicationServices->getPluginManager();

		$plugin = $pluginManager->getPlugin($type, $vendor, $shortName);
		if ($plugin && $plugin->getRegistrationDate())
		{
			if (!$plugin->getConfigured())
			{
				$tm = $applicationServices->getTransactionManager();
				try
				{
					$tm->begin();
					$pluginManager->deregister($plugin);
					$pluginManager->compile();
					$tm->commit();
				}
				catch(\Exception $e)
				{
					$event->addErrorMessage("Error deregistering plugin");
					$applicationServices->getLogging()->exception($e);
					throw $e;
				}
				$event->addInfoMessage('Done!');
			}
			else
			{
				$event->addErrorMessage("Can not deregister configured plugin");
			}
		}
		else
		{
			$event->addInfoMessage('Nothing to do');
		}
	}
}