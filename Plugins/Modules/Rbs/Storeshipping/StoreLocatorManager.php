<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping;

use Zend\XmlRpc\Value\DateTime;

/**
* @name \Rbs\Storeshipping\StoreLocatorManager
*/
class StoreLocatorManager extends \Rbs\Storelocator\StoreManager
{
	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return [parent::getEventManagerIdentifier(), 'StoreLocatorManager'];
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		$storeLocatorManagerListeners = $this->getApplication()->getConfiguredListenerClassNames('Rbs/Commerce/Events/StoreLocatorManager');
		return array_merge(parent::getListenerAggregateClassNames(), $storeLocatorManagerListeners);
	}

	/**
	 * @param array $latLng ['latitude' => float, 'longitude' => float]
	 * @param string $distance
	 * @param array $context ['website' => document, 'data' => array]
	 * @return array
	 */
	public function getPickUpStoresDataAt(array $latLng, $distance = '50km', array $context = [])
	{
		if (count($latLng) == 2 && isset($latLng['latitude']) && isset($latLng['longitude']))
		{
			if (!isset($context['data']) || !is_array($context['data']))
			{
				$context['data'] = [];
			}
			$context['data']['coordinates'] = $latLng;
			$context['data']['distance'] = $distance;
			if (!isset($context['data']['facetFilters']) || !is_array($context['data']['facetFilters']))
			{
				$context['data']['facetFilters'] = [];
			}
			$context['data']['facetFilters']['storeAllow']['allowPickUp'] = 1;
			return $this->getStoresData($context);
		}
		return [];
	}

	/**
	 * @param \Rbs\Storelocator\Documents\Store $store
	 * @param \DateInterval $preparationInterval
	 * @param \DateTime $start
	 * @return \DateTime|null
	 */
	public function evaluatePickUpDateTime(\Rbs\Storelocator\Documents\Store $store, \DateInterval $preparationInterval = null, \DateTime $start = null)
	{
		if (!$start)
		{
			$start = new \DateTime('now', new \DateTimeZone('UTC'));
		}
		else
		{
			$start = new \DateTime($start->format('Y-m-d H:i:s+0000'));
		}


		if ($store->getPreparationInterval()) {
			$preparationInterval = new  \DateInterval($store->getPreparationInterval());
		}

		if (!$preparationInterval)
		{
			$preparationInterval = new  \DateInterval('PT1H');
		}

		list($weekOpeningHours, $specialOpeningHours) = $this->buildStoreOpeningHours($store);
		$publicHolidays = $this->buildStorePublicHolidays($store);

		$maxDaysScan = 10;
		for ($i = 0; $i < $maxDaysScan; $i++)
		{
			$openingHours = $this->findOpeningHours($start, $specialOpeningHours, $publicHolidays, $weekOpeningHours);
			$pickUpDateTime = $openingHours->getOpenDateTime($start, $preparationInterval);
			if ($pickUpDateTime)
			{
				return $pickUpDateTime;
			}
			$start = $openingHours->getDateTime()->add(new \DateInterval('P1D'));
		}
		return null;
	}

	/**
	 * @param \DateTime $at
	 * @param \Rbs\Storeshipping\Planning\OpeningHoursDay[] $specialOpeningHours
	 * @param \Rbs\Storeshipping\Planning\OpeningHoursDay[] $publicHolidays
	 * @param \Rbs\Storeshipping\Planning\OpeningHoursDay[] $weekOpeningHours
	 * @return \Rbs\Storeshipping\Planning\OpeningHoursDay
	 */
	protected function findOpeningHours(\DateTime $at, array $specialOpeningHours, array $publicHolidays, array $weekOpeningHours)
	{
		$testDate = $at->format('Y-m-d');
		if (isset($specialOpeningHours[$testDate]))
		{
			/** @var \Rbs\Storeshipping\Planning\OpeningHoursDay test */
			return $specialOpeningHours[$testDate];
		}
		elseif (isset($publicHolidays[$testDate]))
		{
			return $publicHolidays[$testDate];
		}
		else
		{
			$num = $at->format('w');
			if (isset($weekOpeningHours[$num]))
			{
				$openingHours = $weekOpeningHours[$num];
			}
			else
			{
				$openingHours = new \Rbs\Storeshipping\Planning\OpeningHoursDay();
			}
			$openingHours->setDateTime($at);
			return $openingHours;
		}
	}

	/**
	 * @param \Rbs\Storelocator\Documents\Store $store
	 * @return array
	 */
	protected function buildStoreOpeningHours(\Rbs\Storelocator\Documents\Store $store)
	{
		/** @var \Rbs\Storeshipping\Planning\OpeningHoursDay[] $weekOpeningHours */
		$weekOpeningHours = [];
		foreach ($store->getOpeningHours() as $num => $hoursArray)
		{
			$d = new \Rbs\Storeshipping\Planning\OpeningHoursDay();
			$d->setOpeningHours($hoursArray['amBegin'], $hoursArray['amEnd'], $hoursArray['pmBegin'], $hoursArray['pmEnd']);
			$weekOpeningHours[strval($num)] = $d;
		}

		/** @var \Rbs\Storeshipping\Planning\OpeningHoursDay[] $specialOpeningHours */
		$specialOpeningHours = [];
		foreach ($store->getSpecialDays() as $hoursArray)
		{
			$d = new \Rbs\Storeshipping\Planning\OpeningHoursDay();
			$d->setDateTime(new \DateTime($hoursArray['date']));
			$d->setOpeningHours($hoursArray['amBegin'], $hoursArray['amEnd'], $hoursArray['pmBegin'], $hoursArray['pmEnd']);
			$specialOpeningHours[$d->getDateTime()->format('Y-m-d')] = $d;
		}
		return [$weekOpeningHours, $specialOpeningHours];
	}

	/**
	 * @var array
	 */
	protected $publicHolidays = null;

	/**
	 * @param \Rbs\Storelocator\Documents\Store $store
	 * @return \Rbs\Storeshipping\Planning\OpeningHoursDay[]
	 */
	protected function buildStorePublicHolidays(\Rbs\Storelocator\Documents\Store $store)
	{
		if ($this->publicHolidays === null)
		{
			$this->publicHolidays = [];
			$fileDef = __DIR__ . '/Assets/publicHolidays.json';
			foreach (json_decode(file_get_contents($fileDef), true) as $countryCode => $dates)
			{
				foreach ($dates as $date)
				{
					$d = new \Rbs\Storeshipping\Planning\OpeningHoursDay();
					$d->setDateTime(new \DateTime($date));
					$this->publicHolidays[$countryCode][$d->getDateTime()->format('Y-m-d')] = $d;
				}
			}
		}

		/** @var \Rbs\Storeshipping\Planning\OpeningHoursDay[] $publicHolidays */
		$publicHolidays = [];
		$address = $store->getAddress();
		if ($address)
		{
			$countryCode = $address->getCountryCode();
			if ($countryCode && isset($this->publicHolidays[$countryCode]))
			{
				$publicHolidays = $this->publicHolidays[$countryCode];
				return $publicHolidays;
			}
			return $publicHolidays;
		}
		return $publicHolidays;
	}
}