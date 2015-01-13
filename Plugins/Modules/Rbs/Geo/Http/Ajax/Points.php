<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Http\Ajax;

/**
 * @name \Rbs\Geo\Http\Ajax\Points
 */
class Points
{
	/**
	 * Default actionPath: Rbs/Geo/Points/
	 * Event params:
	 *  - data:
	 *    - address:
	 *       - country
	 *       - zipCode
	 *       - city
	 *    - position:
	 *       - latitude
	 *       - longitude
	 *    - options:
	 *       - modeId
	 *    - matchingZone: string or array
	 * @param \Change\Http\Event $event
	 */
	public function getList(\Change\Http\Event $event)
	{
		$genericServices = $event->getServices('genericServices');
		if ($genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$geoManager = $genericServices->getGeoManager();
			$event->setParam('detailed', false);
			$pointsData = $geoManager->getPoints($event->paramsToArray());
			$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Geo/Address/', $pointsData);
			$result->setPaginationCount(count($pointsData));
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Geo/CoordinatesByAddress
	 * Event params:
	 *  - data:
	 *    - address:
	 * @param \Change\Http\Event $event
	 */
	public function getCoordinatesByAddress(\Change\Http\Event $event)
	{
		$data = $event->getParam('data');
		if (!is_array($data))
		{
			return;
		}
		$address = isset($data['address']) ? $data['address'] : null;
		$genericServices = $event->getServices('genericServices');
		if ($address && $genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$geoManager = $genericServices->getGeoManager();
			$coordinates = $geoManager->getCoordinatesByAddress(new \Rbs\Geo\Address\BaseAddress($address));
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Geo/CoordinatesByAddress', $coordinates);
			$event->setResult($result);
		}
	}
}