<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\CompileI18n
 */
class CompileI18n
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$event->getApplicationServices()->getI18nManager()->compileCoreI18nFiles();
		foreach($event->getApplicationServices()->getPluginManager()->getInstalledPlugins() as $plugin)
		{
			if ($plugin->isAvailable())
			{
				$event->getApplicationServices()->getI18nManager()->compilePluginI18nFiles($plugin);
			}
		}
		$event->getCommandResponse()->addInfoMessage('Done.');
	}
}