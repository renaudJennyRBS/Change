<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Events\GeoManager;

/**
 * @name \Rbs\Storeshipping\Events\GeoManager\GeoManagerEvents
 */
class GeoManagerEvents
{
	/**
	 * Default context params:
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
	 * @param \Change\Events\Event $event
	 */
	public function onGetPoints($event)
	{
		$points = $event->getParam('points');
		if (is_array($points))
		{
			return;
		}
		$storelocatorServices = $event->getServices('Rbs_StorelocatorServices');
		if (!($storelocatorServices instanceof \Rbs\Storelocator\StorelocatorServices))
		{
			return;
		}

		$context = $event->getParam('context') + ['data' => ['position' => [], 'options' => [], 'matchingZone' => null]];
		$data = $context['data'];

		if (isset($data['options']['modeId']) && is_numeric($data['options']['modeId']) && $data['position'])
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$mode = $documentManager->getDocumentInstance($data['options']['modeId']);
			if ($mode instanceof \Rbs\Storeshipping\Documents\RelayMode)
			{
				/** @var \Rbs\Geo\GeoManager $geoManager */
				$geoManager = $event->getTarget();
				$matchingZone = $data['matchingZone'];
				$points = [];

				$storesDataContext = new \Change\Http\Ajax\V1\Context($event->getApplication(), $documentManager, $context);
				$storesDataContext->addData('coordinates', $data['position']);
				$storesDataContext->setPagination([0, 20]);
				$storesDataContext->setURLFormats('canonical');
				$storesDataContext->setVisualFormats('listItem');
				$storesDataContext->setDataSetNames('description,address,coordinates,hours');
				$storesDataContext->addData('facetFilters', ['storeAllow' => ['allowRelayMode' => 1]]);
				$commercialSignId = $mode->getCommercialSignId();
				if ($commercialSignId)
				{
					$storesDataContext->addData('commercialSign', $commercialSignId);
				}
				$data = $storelocatorServices->getStoreManager()->getStoresData($storesDataContext->toArray());
				foreach ($data['items'] as $storeData)
				{
					$point = new \Rbs\Geo\Map\Point(null);
					$point->setTitle($storeData['common']['title']);
					$point->setCode($storeData['common']['code']);
					$point->setLatitude($storeData['coordinates']['latitude']);
					$point->setLongitude($storeData['coordinates']['longitude']);

					$address = new  \Rbs\Geo\Address\BaseAddress($storeData['address']);
					$point->setAddress($address);

					$checkMatchingZone = $this->checkMatchingAddress($address, $matchingZone, $geoManager);
					if (!$checkMatchingZone)
					{
						continue;
					}
					$hours = $storeData['hours']['openingHours'];
					$options = [
						'matchingZone' => $checkMatchingZone,
						'distance' => null,
						'timeSlot' => []
					];

					if (isset($storeData['coordinates']['distance']))
					{
						$options['distanceInfo'] = [$storeData['coordinates']['distance'], $storeData['coordinates']['distanceUnite']];
						$options['distance'] = round($storeData['coordinates']['distance'], 1) . $storeData['coordinates']['distanceUnite'];
					}
					foreach ($hours as $dayHourData)
					{
						$options['timeSlot'][] = [
							'dayName' => $dayHourData['title'],
							'schedule' => $this->formatHours($dayHourData)
						];
					}

					if (isset($storeData['coordinates']['marker']))
					{
						$options['iconUrl'] = $storeData['coordinates']['marker']['original'];
						$options['iconSize'] = $storeData['coordinates']['marker']['size'];
					}

					if (isset($storeData['common']['visuals'][0])) {
						$options['pictureUrl'] = $storeData['common']['visuals'][0]['listItem'];
					}

					if (isset($storeData['common']['description'])) {
						$options['description'] = $storeData['common']['description'];
					}
					$point->setOptions($options);
					$points[] = $point->toArray();
				}
				$event->setParam('points', $points);
			}
		}
	}

	/**
	 * @param \Rbs\Geo\Address\AddressInterface $address
	 * @param string|string[] $matchingZone
	 * @param \Rbs\Geo\GeoManager $geoManager
	 * @return boolean
	 */
	protected function checkMatchingAddress($address, $matchingZone, $geoManager)
	{
		if (!$matchingZone)
		{
			return true;
		}
		elseif (is_string($matchingZone))
		{
			$match = true;
			$zone = $geoManager->getZoneByCode($matchingZone);
			if ($zone)
			{
				$match = $geoManager->isValidAddressForZone($address, $matchingZone);
			}
			return $match ? $matchingZone : false;
		}
		elseif (is_array($matchingZone))
		{
			$match = false;
			foreach ($matchingZone as $zone)
			{
				if (is_string($zone))
				{
					$match = $this->checkMatchingAddress($address, $zone, $geoManager);
					if ($match)
					{
						break;
					}
				}
			}
			return $match;
		}
		return false;
	}

	protected function formatHours($dayHourData)
	{
		if (!isset($dayHourData['amBegin']) && !isset($dayHourData['pmEnd']))
		{
			return [];
		}
		elseif (!isset($dayHourData['amEnd']) && !isset($dayHourData['pmBegin']))
		{
			return [[$dayHourData['amBegin'], $dayHourData['pmEnd']]];
		}
		elseif (isset($dayHourData['amEnd']) && isset($dayHourData['pmBegin']))
		{
			return [
				[$dayHourData['amBegin'] , $dayHourData['amEnd']],
				[$dayHourData['pmBegin'] , $dayHourData['pmEnd']]
			];
		}
		elseif (isset($dayHourData['amBegin']))
		{
			return [[$dayHourData['amBegin'] , $dayHourData['amEnd']], null];
		}
		else
		{
			return [null, [$dayHourData['pmBegin'], $dayHourData['pmEnd']]];
		}
	}
}