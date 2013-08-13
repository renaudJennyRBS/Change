<?php
namespace Rbs\Price\Collection;

use Change\Collection\CollectionArray;
use Zend\EventManager\Event;

/**
 * @name \Rbs\Price\Collection\Collections
 */
class Collections
{
	/**
	 * @param Event $event
	 */
	public function addBillingAreasForWebStore(Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		$webStoreId = $event->getParam('webStoreId');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$items = array();
			if (intval($webStoreId) > 0)
			{
				$webStore = $documentServices->getDocumentManager()->getDocumentInstance($webStoreId);
				if ($webStore instanceof \Rbs\Store\Documents\WebStore)
				{
					foreach ($webStore->getBillingAreas() as $area)
					{
						$items[$area->getId()] = $area->getLabel();
					}
				}
			}
			$collection = new CollectionArray('Rbs_Price_Collection_BillingAreasForWebStore', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param Event $event
	 */
	public function addTaxRoundingStrategyCollection(Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
			$collection = new CollectionArray('Rbs_Price_Collection_TaxRoundingStrategy', array(
				'u' => $i18nManager->trans('m.rbs.price.collection.taxroundingstrategy.on-unit-value'),
				'l' => $i18nManager->trans('m.rbs.price.collection.taxroundingstrategy.on-line-value'),
				't' => $i18nManager->trans('m.rbs.price.collection.taxroundingstrategy.on-total-value')
			));
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	public function addIso4217Collection(Event $event)
	{
		$collection = new Iso4217Collection();
		$event->setParam('collection', $collection);
		$event->stopPropagation();
	}
}