<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Events\StoreLocator;

/**
* @name \Rbs\Storeshipping\Events\StoreLocator\StoreLocatorIndexEvents
*/
class StoreLocatorIndexEvents
{
	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onGetFacetsDefinition(\Change\Documents\Events\Event $event)
	{
		$facetsDefinition = $event->getParam('facetsDefinition');
		if (is_array($facetsDefinition))
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$facetsDefinition[] = (new \Rbs\Storeshipping\Facet\StoreAllowFacetDefinition($i18nManager));
			$event->setParam('facetsDefinition', $facetsDefinition);
		}
	}
}