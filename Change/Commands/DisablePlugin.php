<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\DisablePlugin
 */
class DisablePlugin
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
		if ($plugin && $plugin->getActivated())
		{
			$plugin->setActivated(false);
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();
				$pluginManager->update($plugin);
				$tm->commit();
			}
			catch(\Exception $e)
			{
				$event->addErrorMessage("Error disabling plugin");
				$applicationServices->getLogging()->exception($e);
				throw $e;
			}
			$pluginManager->compile();
			$event->addInfoMessage('Done.');
		}
		else
		{
			$event->addInfoMessage('Nothing to do');
		}
	}
}