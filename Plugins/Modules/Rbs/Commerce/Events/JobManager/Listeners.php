<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\JobManager;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\JobManager\Listeners
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
		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\InitializeItemsForSectionList())->execute($event);
		};
		$events->attach('process_Rbs_Catalog_InitializeItemsForSectionList', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\CleanUpListItems())->execute($event);
		};
		$events->attach('process_Change_Document_CleanUp', $callBack, 10);

		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\CleanUpAttribute())->execute($event);
		};
		$events->attach('process_Change_Document_CleanUp', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\AttributeRefreshValues())->execute($event);
		};
		$events->attach('process_Rbs_Catalog_Attribute_Refresh_Values', $callBack, 5);
		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\UpdateSymmetricalProductListItem())->execute($event);
		};
		$events->attach('process_Rbs_Catalog_UpdateSymmetricalProductListItem', $callBack, 15);

		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\VariantConfiguration())->execute($event);
		};
		$events->attach('process_Rbs_Catalog_VariantConfiguration', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\AxesConfiguration())->execute($event);
		};
		$events->attach('process_Rbs_Catalog_AxesConfiguration', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Price\Job\UpdateTax())->execute($event);
		};
		$events->attach('process_Rbs_Price_UpdateTax', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Payment\Job\TransactionStatusChanged())->execute($event);
		};
		$events->attach('process_Rbs_Payment_TransactionStatusChanged', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Commerce\Job\TransactionStatusChanged())->execute($event);
		};
		$events->attach('process_Rbs_Payment_TransactionStatusChanged', $callBack, 10);

		$callBack = function ($event)
		{
			(new \Rbs\Commerce\Job\CartsCleanup())->execute($event);
		};
		$events->attach('process_Rbs_Commerce_Carts_Cleanup', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Order\Job\OrderComplete())->execute($event);
		};
		$events->attach('process_Rbs_Order_Order_Complete', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Order\Job\OrderCleanup())->execute($event);
		};
		$events->attach('process_Change_Document_CleanUp', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Events\ItemOrderingUpdater())->onScheduledActivation($event);
		};
		$events->attach('process_scheduledActivation', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Stock\Job\UpdateProductAvailability())->execute($event);
		};
		$events->attach('process_Rbs_Stock_UpdateProductAvailability', $callBack, 5);
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