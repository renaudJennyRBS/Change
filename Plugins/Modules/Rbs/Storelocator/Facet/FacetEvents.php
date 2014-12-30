<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Facet;

/**
* @name \Rbs\Storelocator\Facet\FacetEvents
*/
class FacetEvents
{
	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onSave(\Change\Documents\Events\Event $event)
	{
		/** @var \Rbs\Elasticsearch\Documents\Facet $facet */
		$facet = $event->getDocument();
		if (!($facet instanceof \Rbs\Elasticsearch\Documents\Facet))
		{
			return;
		}

		if ($facet->isPropertyModified('parametersData') || $facet->isPropertyModified('configurationType'))
		{
			switch ($facet->getConfigurationType())
			{
				case 'StorelocatorTerritorialUnit':
					$facetDefinition = new TerritorialUnitFacetDefinition($facet);
					$facetDefinition->setDocumentManager($event->getApplicationServices()->getDocumentManager());
					$facetDefinition->validateConfiguration($facet);
					$facet->saveWrappedProperties();
					break;
				case 'StorelocatorCountry':
					$facetDefinition = new CountryFacetDefinition($facet);
					$facetDefinition->setDocumentManager($event->getApplicationServices()->getDocumentManager());
					$facetDefinition->setI18nManager($event->getApplicationServices()->getI18nManager());
					$facetDefinition->validateConfiguration($facet);
					$facet->saveWrappedProperties();
					break;
			}
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onGetFacetDefinition(\Change\Documents\Events\Event $event)
	{
		/** @var $facet \Rbs\Elasticsearch\Documents\Facet */
		$facet = $event->getDocument();
		if (!($facet instanceof \Rbs\Elasticsearch\Documents\Facet))
		{
			return;
		}

		$applicationServices = $event->getApplicationServices();
		switch ($facet->getConfigurationType())
		{
			case 'StorelocatorTerritorialUnit':
				$facetDefinition = new TerritorialUnitFacetDefinition($facet);
				$event->setParam('facetDefinition', $facetDefinition);
				break;
			case 'StorelocatorCountry':
				$facetDefinition = new CountryFacetDefinition($facet);
				$facetDefinition->setI18nManager($applicationServices->getI18nManager());
				$event->setParam('facetDefinition', $facetDefinition);
				break;
		}
	}
}