<?php
namespace Change\Commands;

use Change\Commands\Events\Event;


/**
 * @name \Change\Commands\InstallPlugin
 */
class InstallPlugin
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

		$toInstall = $pluginManager->getPlugin($type, $vendor, $shortName);

		if ($toInstall && $toInstall->getRegistrationDate())
		{
			$plugins = $pluginManager->installPlugin($type, $vendor, $shortName, array());
			$pluginManager->compile();

			if (count($plugins))
			{
				foreach ($plugins as $plugin)
				{
					$event->addInfoMessage($plugin . ' installed');
				}
				$event->addInfoMessage(count($plugins) . ' plugin(s) installed.');
			}
			else
			{
				$event->addInfoMessage('Nothing installed');
			}
		}
		else
		{
			$event->addErrorMessage("Plugin does not exist");
		}


	}
}