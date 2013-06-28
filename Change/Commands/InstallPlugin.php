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
		$pluginManager->compile();

		$plugins = $pluginManager->installPlugin($type, $vendor, $shortName, array());

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
			$event->addInfoMessage('Plugin not installed.');
		}
	}
}