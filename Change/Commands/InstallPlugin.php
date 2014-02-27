<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		$applicationServices = $event->getApplicationServices();

		$type = $event->getParam('type');
		$vendor = $event->getParam('vendor');
		$shortName = $event->getParam('name');

		$response = $event->getCommandResponse();

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
					$response->addInfoMessage($plugin . ' installed');
				}
				$response->addInfoMessage(count($plugins) . ' plugin(s) installed.');
			}
			else
			{
				$response->addInfoMessage('Nothing installed');
			}
		}
		else
		{
			$response->addErrorMessage("Plugin does not exist");
		}


	}
}