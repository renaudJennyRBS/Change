<?php
namespace Change\Commands;

use Change\Commands\Events\Event;


/**
 * @name \Change\Commands\InstallPackage
 */
class InstallPackage
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$vendor = $event->getParam('vendor');
		$shortName = $event->getParam('name');

		$pluginManager = $applicationServices->getPluginManager();
		$pluginManager->compile();

		$plugins = $pluginManager->installPackage($vendor, $shortName, array());

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
			$event->addInfoMessage('Package not installed.');
		}
	}
}