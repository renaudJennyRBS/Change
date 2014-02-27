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
 * @name \Change\Commands\DeregisterPlugin
 */
class DeregisterPlugin
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

		$response = $event->getCommandResponse();

		$pluginManager = $applicationServices->getPluginManager();

		$plugin = $pluginManager->getPlugin($type, $vendor, $shortName);
		if ($plugin && $plugin->getRegistrationDate())
		{
			if (!$plugin->getConfigured())
			{
				$tm = $applicationServices->getTransactionManager();
				try
				{
					$tm->begin();
					$pluginManager->deregister($plugin);
					$pluginManager->compile();
					$tm->commit();
				}
				catch(\Exception $e)
				{
					$response->addErrorMessage("Error deregistering plugin");
					$applicationServices->getLogging()->exception($e);
					throw $e;
				}
				$response->addInfoMessage('Done!');
			}
			else
			{
				$response->addErrorMessage("Can not deregister configured plugin");
			}
		}
		else
		{
			$response->addInfoMessage('Nothing to do');
		}
	}
}