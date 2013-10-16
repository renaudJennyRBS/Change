<?php
namespace Change\Commands;

use Change\Commands\Events\Event;


/**
 * @name \Change\Commands\RegisterPlugins
 */
class RegisterPlugin
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$applicationServices = new \Change\Application\ApplicationServices($application);

		$pluginManager = $applicationServices->getPluginManager();

		if ($event->getParam('all'))
		{
			$plugins = $pluginManager->getUnregisteredPlugins();

			$applicationServices->getTransactionManager()->begin();
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();
				foreach ($plugins as $plugin)
				{
					$pluginManager->register($plugin);
					$event->addInfoMessage($plugin . ' registered');
				}
				$tm->commit();
			}
			catch(\Exception $e)
			{
				$event->addErrorMessage("Error registering plugins");
				$applicationServices->getLogging()->exception($e);
				throw $e;
			}
			$nbPlugins = count($plugins);
			$event->addInfoMessage($nbPlugins. ' new plugins registered');

			$plugins = $pluginManager->compile();
			$nbPlugins = count($plugins);
			$event->addInfoMessage($nbPlugins. ' plugins registered.');
		}
		else if (!$event->getParam('name'))
		{
			$event->addErrorMessage("You must at least specify a plugin name");
		}
		else
		{
			$found = false;
			$type = $event->getParam('type');
			$vendor = $event->getParam('vendor');
			$shortName = $event->getParam('name');
			foreach ($pluginManager->getUnregisteredPlugins() as $plugin)
			{
				if ($plugin->getType() === $type && $plugin->getVendor() === $vendor && $plugin->getShortName() === $shortName)
				{
					$found = true;
					$tm = $applicationServices->getTransactionManager();
					try
					{
						$tm->begin();
						$pluginManager->register($plugin);
						$pluginManager->compile();
						$tm->commit();
					}
					catch(\Exception $e)
					{
						$event->addErrorMessage("Error registering plugin");
						$applicationServices->getLogging()->exception($e);
						throw $e;
					}
					$event->addMessage("Done!");
				}
			}
			if (!$found)
			{
				$event->addErrorMessage("No such unregistered plugin!");
			}
		}
	}
}