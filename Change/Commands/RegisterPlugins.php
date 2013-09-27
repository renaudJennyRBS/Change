<?php
namespace Change\Commands;

use Change\Commands\Events\Event;


/**
 * @name \Change\Commands\RegisterPlugins
 */
class RegisterPlugins
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$applicationServices = new \Change\Application\ApplicationServices($application);

		$pluginManager = $applicationServices->getPluginManager();
		$plugins = $pluginManager->getUnregisteredPlugins();

		$applicationServices->getTransactionManager()->begin();
		foreach ($plugins as $plugin)
		{
			$pluginManager->register($plugin);
			$event->addInfoMessage($plugin . ' registered');
		}
		$applicationServices->getTransactionManager()->commit();

		$nbPlugins = count($plugins);
		$event->addInfoMessage($nbPlugins. ' new plugins registered');

		$plugins = $pluginManager->compile();
		$nbPlugins = count($plugins);
		$event->addInfoMessage($nbPlugins. ' plugins registered.');
	}
}