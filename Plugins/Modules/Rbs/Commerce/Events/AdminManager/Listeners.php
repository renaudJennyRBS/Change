<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\AdminManager;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\AdminManager\Listeners
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
		$callback = function ($event)
		{
			(new \Rbs\Commerce\Admin\GetModelTwigAttributes())->execute($event);
			(new \Rbs\Catalog\Admin\GetModelTwigAttributes())->execute($event);
			(new \Rbs\Price\Admin\GetModelTwigAttributes())->execute($event);
			(new \Rbs\Stock\Admin\GetModelTwigAttributes())->execute($event);

		};
		//Priority 1 (default value) to be sure to get the default attributes
		$events->attach('getModelTwigAttributes', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Commerce\Commands\InitializeWebStore())->getGenericSettingsStructures($event);
			(new \Rbs\Commerce\Commands\InitializeOrderProcess())->getGenericSettingsStructures($event);
			(new \Rbs\Commerce\Commands\InitializeReturnProcess())->getGenericSettingsStructures($event);
		};
		$events->attach('getGenericSettingsStructures', $callback, 5);

		$callback = function ($event)
		{
			(new \Rbs\Order\Admin\SearchDocuments())->execute($event);
			(new \Rbs\Payment\Admin\SearchDocuments())->execute($event);
			(new \Rbs\Stock\Admin\SearchDocuments())->execute($event);
		};
		$events->attach('searchDocuments', $callback, 10);
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