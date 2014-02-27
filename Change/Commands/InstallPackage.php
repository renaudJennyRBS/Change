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

		$response = $event->getCommandResponse();

		$pluginManager = $applicationServices->getPluginManager();
		$pluginManager->compile();

		$plugins = $pluginManager->installPackage($vendor, $shortName, array());

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
			$response->addInfoMessage('Package not installed.');
		}
	}
}