<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\CompileDocuments
 */
class CompilePluginsRegistration
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$applicationServices = new \Change\Application\ApplicationServices($application);
		$pluginManager = $applicationServices->getPluginManager();
		$plugins = $pluginManager->compile();
		$nbPlugins = count($plugins);
		$event->addInfoMessage($nbPlugins. ' plugins registered');
	}
}