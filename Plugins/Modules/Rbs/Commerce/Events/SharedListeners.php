<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events;

use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\SharedListeners
 */
class SharedListeners implements SharedListenerAggregateInterface
{
	/**
	 * @var \Rbs\Commerce\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$events->attach('*', '*', function($event) {
			if ($event instanceof \Change\Events\Event)
			{
				if ($this->commerceServices === null) {

					$this->commerceServices = new \Rbs\Commerce\CommerceServices($event->getApplication(), $event->getApplicationServices());
				}
				$event->getServices()->set('commerceServices', $this->commerceServices);
			}
			return true;
		}, 9997);

		//Rbs_Catalog
		$events->attach('Rbs_Catalog_ProductListItem', ['documents.created', 'documents.updated'], function ($event)
		{
			(new \Rbs\Catalog\Events\ItemOrderingUpdater)->onItemChange($event);
		}, 5);

		$events->attach('Rbs_Price_Price', ['documents.created', 'documents.updated'], function ($event)
		{
			(new \Rbs\Catalog\Events\ItemOrderingUpdater)->onPriceChange($event);
		}, 5);

		$events->attach('Rbs_Stock_InventoryEntry', ['documents.created', 'documents.updated'], function ($event)
		{
			(new \Rbs\Catalog\Events\ItemOrderingUpdater)->onInventoryEntryChange($event);
		}, 5);

		$events->attach('Rbs_Stock_Sku', ['documents.updated'], function ($event)
		{
			(new \Rbs\Catalog\Events\ItemOrderingUpdater)->onSkuChange($event);
		}, 5);

		//Rbs_Stock
		$events->attach('Rbs_Catalog_Product', ['documents.update'], function ($event)
		{
			(new \Rbs\Stock\Job\UpdateProductAvailability())->onProductSkuChange($event);
		}, 5);

		$events->attach('Rbs_Stock_InventoryEntry', ['documents.created', 'documents.updated'], function ($event)
		{
			(new \Rbs\Stock\Job\UpdateProductAvailability())->onInventoryEntryChange($event);
		}, 10);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		//TODO: Implement detachShared() method.
	}
}