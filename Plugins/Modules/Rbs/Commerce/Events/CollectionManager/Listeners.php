<?php
namespace Rbs\Commerce\Events\CollectionManager;

use Zend\EventManager\Event;
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
			switch ($event->getParam('code'))
			{
				case 'Rbs_Catalog_Collection_ProductSortOrders':
					(new \Rbs\Catalog\Collection\Collections())->addProductSortOrders($event);
					break;
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
				case 'Rbs_Price_Collection_BillingAreasForWebStore':
					(new \Rbs\Price\Collection\Collections())->addBillingAreasForWebStore($event);
					break;
				case 'Rbs_Price_Collection_Iso4217':
					(new \Rbs\Price\Collection\Collections())->addIso4217Collection($event);
					break;
				case 'Rbs_Price_Collection_TaxRoundingStrategy':
					(new \Rbs\Price\Collection\Collections())->addTaxRoundingStrategyCollection($event);
					break;
				case 'Rbs_Store_Collection_WebStores':
					(new \Rbs\Store\Collection\Collections())->addWebStores($event);
					break;
			}
		};
		$events->attach(CollectionManager::EVENT_GET_COLLECTION, $callback, 10);

		$callback = function (Event $event)
		{
			$codes = $event->getParam('codes', array());
			$codes[] = 'Rbs_Catalog_Collection_ProductSortOrders';
			$codes[] = 'Rbs_Catalog_Collection_AttributeValueTypes';
			$codes[] = 'Rbs_Catalog_Collection_AttributeCollections';
			$codes[] = 'Rbs_Catalog_Collection_AttributeSet';
			$codes[] = 'Rbs_Catalog_Collection_AttributeVisibility';
			$codes[] = 'Rbs_Catalog_Collection_AttributeProductProperties';
			$codes[] = 'Rbs_Price_Collection_BillingAreasForWebStore';
			$codes[] = 'Rbs_Price_Collection_Iso4217';
			$codes[] = 'Rbs_Price_Collection_TaxRoundingStrategy';
			$codes[] = 'Rbs_Store_Collection_WebStores';
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