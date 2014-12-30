<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Collection;


/**
 * @name \Rbs\Storelocator\Collection\Collections
 */
class Collections
{

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addFacetConfigurationType(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$collection = $event->getParam('collection');
			if ($collection instanceof \Change\Collection\CollectionArray)
			{
				$i18nManager = $applicationServices->getI18nManager();

				$label =  $i18nManager->trans('m.rbs.storelocator.admin.facet_territorial_unit', array('ucf'));
				$collection->addItem('StorelocatorTerritorialUnit', $label);

				$label =  $i18nManager->trans('m.rbs.storelocator.admin.facet_country', array('ucf'));
				$collection->addItem('StorelocatorCountry', $label);
			}
		}
	}
}