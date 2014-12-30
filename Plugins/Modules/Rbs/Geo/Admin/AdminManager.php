<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Admin;

/**
* @name \Rbs\Geo\Admin\AdminManager
*/
class AdminManager
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onGetHomeAttributes(\Change\Events\Event $event)
	{
		$attributes = $event->getParam('attributes');
		if (is_array($attributes)) {
			$attributes['scripts'][] = 'http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js';
			$attributes['scripts'][] = 'http://matchingnotes.com/javascripts/leaflet-google.js';

			$attributes['styles'][] = 'http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css';

			$event->setParam('attributes', $attributes);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onSearchDocument(\Change\Events\Event $event)
	{
		$modelName = $event->getParam('modelName');
		if (in_array($modelName, ['Rbs_Geo_Country', 'Rbs_Geo_Zone', 'Rbs_Geo_TerritorialUnit']))
		{
			$event->setParam('propertyNames', ['label', 'code']);
		}
	}
} 