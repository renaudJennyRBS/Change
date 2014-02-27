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
 * @name \Change\Commands\RegisterPlugins
 */
class RegisterPlugin
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$response = $event->getCommandResponse();

		$pluginManager = $applicationServices->getPluginManager();

		if ($event->getParam('all'))
		{
			$plugins = $pluginManager->getUnregisteredPlugins();

			$tm = $applicationServices->getTransactionManager();
			try
			{
				$tm->begin();
				foreach ($plugins as $plugin)
				{
					$pluginManager->register($plugin);
					$response->addInfoMessage($plugin . ' registered');
				}
				$tm->commit();
			}
			catch(\Exception $e)
			{
				$response->addErrorMessage("Error registering plugins");
				$applicationServices->getLogging()->exception($e);
				throw $e;
			}
			$nbPlugins = count($plugins);
			$response->addInfoMessage($nbPlugins. ' new plugins registered');

			$plugins = $pluginManager->compile();
			$nbPlugins = count($plugins);
			$response->addInfoMessage($nbPlugins. ' plugins registered.');
		}
		else if (!$event->getParam('name'))
		{
			$response->addErrorMessage("You must at least specify a plugin name");
		}
		else
		{
			$found = false;
			$type = $event->getParam('type');
			$vendor = $event->getParam('vendor');
			$shortName = $event->getParam('name');
			foreach ($pluginManager->getUnregisteredPlugins() as $plugin)
			{
				if ($plugin->getType() === $type && $plugin->getVendor() === $vendor && $plugin->getShortName() === $shortName)
				{
					$found = true;
					$tm = $applicationServices->getTransactionManager();
					try
					{
						$tm->begin();
						$pluginManager->register($plugin);
						$pluginManager->compile();
						$tm->commit();
					}
					catch(\Exception $e)
					{
						$response->addErrorMessage("Error registering plugin");
						$applicationServices->getLogging()->exception($e);
						throw $e;
					}
					$response->addInfoMessage("Done!");
				}
			}
			if (!$found)
			{
				$response->addErrorMessage("No such unregistered plugin!");
			}
		}
	}
}