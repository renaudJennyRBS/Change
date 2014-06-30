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
 * @name \Change\Commands\DeinstallPlugin
 */
class DeinstallPlugin extends AbstractPluginCommand
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$this->initWithEvent($event);
		$type = $this->getType();
		$vendor = $this->getVendor();
		$shortName = $this->getShortName();

		$applicationServices = $event->getApplicationServices();
		$response = $event->getCommandResponse();

		$pluginManager = $applicationServices->getPluginManager();

		$plugin = $pluginManager->getPlugin($type, $vendor, $shortName);
		if ($plugin && $plugin->getRegistrationDate() && $plugin->getConfigured())
		{
			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();
				$pluginManager->deinstall($plugin);
				$pluginManager->compile();
				$tm->commit();
			}
			catch(\Exception $e)
			{
				$response->addErrorMessage("Error deinstall plugin");
				$applicationServices->getLogging()->exception($e);
				throw $e;
			}
		}
		else
		{
			$response->addInfoMessage('Nothing to do');
		}
	}
}