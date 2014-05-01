<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\Commands;

use Change\Commands\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Json\Json;

/**
 * @name \Rbs\Commerce\Events\Commands\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			$commandConfigPath = __DIR__ . '/Assets/config.json';
			return Json::decode(file_get_contents($commandConfigPath), Json::TYPE_ARRAY);
		};
		$events->attach('config', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Catalog\Commands\ExportAttributes())->execute($event);
		};
		$events->attach('rbs_catalog:export-attributes', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Catalog\Commands\ImportAttributes())->execute($event);
		};
		$events->attach('rbs_catalog:import-attributes', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Commerce\Commands\InitializeWebStore())->execute($event);
		};
		$events->attach('rbs_commerce:initialize-web-store', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Commerce\Commands\InitializeOrderProcess())->execute($event);
		};
		$events->attach('rbs_commerce:initialize-order-process', $callback);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}