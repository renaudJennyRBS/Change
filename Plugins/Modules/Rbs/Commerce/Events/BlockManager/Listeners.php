<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\BlockManager;

use Change\Presentation\Blocks\Standard\RegisterByBlockName;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\BlockManager\Listeners
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
		new RegisterByBlockName('Rbs_Brand_Brand', true, $events);
		new RegisterByBlockName('Rbs_Catalog_ProductList', true, $events);
		new RegisterByBlockName('Rbs_Catalog_Product', true, $events);
		new RegisterByBlockName('Rbs_Catalog_ProductAddedToCart', true, $events);
		new RegisterByBlockName('Rbs_Catalog_CrossSelling', true, $events);
		new RegisterByBlockName('Rbs_Catalog_CartCrossSelling', true, $events);
		new RegisterByBlockName('Rbs_Commerce_Cart', true, $events);
		new RegisterByBlockName('Rbs_Commerce_ShortCart', true, $events);
		new RegisterByBlockName('Rbs_Commerce_OrderProcess', true, $events);
		new RegisterByBlockName('Rbs_Commerce_PaymentReturn', true, $events);
		new RegisterByBlockName('Rbs_Order_CreditNoteSummary', true, $events);
		new RegisterByBlockName('Rbs_Order_OrderDetail', true, $events);
		new RegisterByBlockName('Rbs_Order_OrderList', true, $events);
		new RegisterByBlockName('Rbs_Payment_CreateAccountForTransaction', true, $events);
		new RegisterByBlockName('Rbs_Store_WebStoreSelector', true, $events);
		new RegisterByBlockName('Rbs_Wishlist_WishlistButton', true, $events);
		new RegisterByBlockName('Rbs_Wishlist_WishlistDetail', true, $events);
		new RegisterByBlockName('Rbs_Wishlist_WishlistList', true, $events);
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
