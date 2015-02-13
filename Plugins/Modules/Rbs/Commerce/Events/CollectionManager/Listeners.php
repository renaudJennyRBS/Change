<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\CollectionManager;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Collection\CollectionManager;

/**
 * @name \Rbs\Commerce\Events\CollectionManager\Listeners
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
			if (!$event->getParam('collection'))
			{
				switch ($event->getParam('code'))
				{
					case 'Rbs_Catalog_Collection_AttributeValueTypes':
						(new \Rbs\Catalog\Collection\Collections())->addAttributeValueTypes($event);
						break;
					case 'Rbs_Catalog_Collection_AttributeCollections':
						(new \Rbs\Catalog\Collection\Collections())->addAttributeCollections($event);
						break;
					case 'Rbs_Catalog_Collection_AttributeSet':
						(new \Rbs\Catalog\Collection\Collections())->addAttributeSet($event);
						break;
					case 'Rbs_Catalog_Collection_AttributeVisibility':
						(new \Rbs\Catalog\Collection\Collections())->addAttributeVisibility($event);
						break;
					case 'Rbs_Catalog_Collection_AttributeProductProperties':
						(new \Rbs\Catalog\Collection\Collections())->addAttributeProductProperties($event);
						break;
					case 'Rbs_Catalog_Collection_ProductSortOrders':
						(new \Rbs\Catalog\Collection\Collections())->addProductSortOrders($event);
						break;
					case 'Rbs_Catalog_CrossSelling_CartProductChoiceStrategy':
						(new \Rbs\Catalog\Collection\Collections())->addCartProductChoiceStrategyCollection($event);
						break;
					case 'Rbs_Catalog_InformationDisplayMode':
						(new \Rbs\Catalog\Collection\Collections())->addInformationDisplayMode($event);
						break;
					case 'Rbs_Catalog_SpecificationDisplayMode':
						(new \Rbs\Catalog\Collection\Collections())->addSpecificationDisplayMode($event);
						break;

					case 'Rbs_Commerce_TaxBehavior':
						(new \Rbs\Commerce\Collection\Collections())->addTaxBehavior($event);
						break;

					case 'Rbs_Commerce_ProcessScenario':
						(new \Rbs\Commerce\Collection\Collections())->addProcessScenario($event);
						break;

					case 'Rbs_Discount_Collection_DiscountTypes':
						(new \Rbs\Discount\Collection\Collections())->addDiscountTypes($event);
						break;

					case 'Rbs_Order_ProcessingStatuses':
						(new \Rbs\Order\Collection\Collections())->addProcessingStatuses($event);
						break;

					case 'Rbs_Price_Collection_BillingAreasForWebStore':
						(new \Rbs\Price\Collection\Collections())->addBillingAreasForWebStore($event);
						break;
					case 'Rbs_Price_Collection_Iso4217':
						(new \Rbs\Price\Collection\Collections())->addIso4217Collection($event);
						break;
					case 'Rbs_Price_Collection_ModifierNames':
						(new \Rbs\Price\Modifiers\Modifiers())->addModifierNames($event);
						break;
					case 'Rbs_Price_Collection_TaxRoundingStrategy':
						(new \Rbs\Price\Collection\Collections())->addTaxRoundingStrategyCollection($event);
						break;

					case 'Rbs_Productreturn_FieldDisplayOptions':
						(new \Rbs\Productreturn\Collection\Collections())->addFieldDisplayOptions($event);
						break;

					case 'Rbs_Shipping_Collection_ShippingModes':
						(new \Rbs\Shipping\Collection\Collections())->addShippingModes($event);
						break;

					case 'Rbs_Store_Collection_WebStores':
						(new \Rbs\Store\Collection\Collections())->addWebStores($event);
						break;

					case 'Rbs_Store_WebStoreWarehouses':
						(new \Rbs\Store\Collection\Collections())->addWebStoreWarehouses($event);
						break;
				}
			}
		};
		$events->attach(CollectionManager::EVENT_GET_COLLECTION, $callback, 10);

		$callback = function (Event $event)
		{
			$codes = $event->getParam('codes', []);
			$codes[] = 'Rbs_Catalog_Collection_AttributeValueTypes';
			$codes[] = 'Rbs_Catalog_Collection_AttributeCollections';
			$codes[] = 'Rbs_Catalog_Collection_AttributeSet';
			$codes[] = 'Rbs_Catalog_Collection_AttributeVisibility';
			$codes[] = 'Rbs_Catalog_Collection_AttributeProductProperties';
			$codes[] = 'Rbs_Catalog_Collection_ProductSortOrders';
			$codes[] = 'Rbs_Catalog_CrossSelling_CartProductChoiceStrategy';
			$codes[] = 'Rbs_Catalog_InformationDisplayMode';
			$codes[] = 'Rbs_Catalog_SpecificationDisplayMode';

			$codes[] = 'Rbs_Commerce_TaxBehavior';

			$codes[] = 'Rbs_Commerce_ProcessScenario';

			$codes[] = 'Rbs_Discount_Collection_DiscountTypes';

			$codes[] = 'Rbs_Order_ProcessingStatus';

			$codes[] = 'Rbs_Price_Collection_BillingAreasForWebStore';
			$codes[] = 'Rbs_Price_Collection_ModifierNames';
			$codes[] = 'Rbs_Price_Collection_Iso4217';
			$codes[] = 'Rbs_Price_Collection_TaxRoundingStrategy';

			$codes[] = 'Rbs_Productreturn_FieldDisplayOptions';

			$codes[] = 'Rbs_Shipping_Collection_ShippingModes';

			$codes[] = 'Rbs_Store_Collection_WebStores';
			$codes[] = 'Rbs_Store_WebStoreWarehouses';
			$event->setParam('codes', $codes);
		};
		$events->attach(CollectionManager::EVENT_GET_CODES, $callback, 1);
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