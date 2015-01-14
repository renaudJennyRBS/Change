<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Dev\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Dev\Commands\InitializePlugin
 */
class InitializePlugin
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();

		$applicationServices = $event->getApplicationServices();

		$type = $event->getParam('type');
		$vendor = $event->getParam('vendor');
		$name = $event->getParam('name');
		$package = $event->getParam('package');

		try
		{
			$applicationServices->getTransactionManager()->begin();

			$defaultLCID = $event->getApplicationServices()->getI18nManager()->getDefaultLCID();
			$path = $applicationServices->getPluginManager()->initializePlugin($type, $vendor, $name, $defaultLCID, $package);
			$response->addInfoMessage('Plugin skeleton generated at ' . $path);
			
			$applicationServices->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			$applicationServices->getLogging()->exception($e);
			$response->addErrorMessage($e->getMessage());
			throw $applicationServices->getTransactionManager()->rollback($e);
		}
	}
}