<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Price\Collection;

use Change\Collection\CollectionArray;
use Change\Events\Event;

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
		$applicationServices = $event->getApplicationServices();
		$webStoreId = $event->getParam('webStoreId');
		if ($applicationServices)
		{
			$items = array();
			if (intval($webStoreId) > 0)
			{
				$webStore = $applicationServices->getDocumentManager()->getDocumentInstance($webStoreId);
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
		}
	}

	/**
	 * @param Event $event
	 */
	public function addTaxRoundingStrategyCollection(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18nManager = $applicationServices->getI18nManager();
			$collection = new CollectionArray('Rbs_Price_Collection_TaxRoundingStrategy', array(
				'u' => $i18nManager->trans('m.rbs.price.admin.collection_taxroundingstrategy_on_unit_value'),
				'l' => $i18nManager->trans('m.rbs.price.admin.collection_taxroundingstrategy_on_line_value'),
				't' => $i18nManager->trans('m.rbs.price.admin.collection_taxroundingstrategy_on_total_value')
			));
			$event->setParam('collection', $collection);
		}
	}

	public function addIso4217Collection(Event $event)
	{
		$collection = new Iso4217Collection();
		$event->setParam('collection', $collection);
	}
}