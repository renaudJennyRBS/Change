<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Http\Ajax;

/**
 * @name \Rbs\Geo\Http\Ajax\Address
 */
class Address
{
	/**
	 * Default actionPath: Rbs/Geo/Address/
	 * Event params:
	 *  - data: matchingZone
	 * @param \Change\Http\Event $event
	 */
	public function getList(\Change\Http\Event $event)
	{
		$addressesData = [];

		/** @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');

		$geoManager = $genericServices->getGeoManager();
		$defaultFor = $geoManager->getDefaultForNames();

		$matchingZone = null;
		$data = $event->getParam('data');
		if (is_array($data) && isset($data['matchingZone']))
		{
			$matchingZone = $data['matchingZone'];
		}

		$defaultFieldValuesFor = [];
		foreach ($geoManager->getDefaultAddresses($defaultFor) as $for => $defaultAddress)
		{
			if ($defaultAddress instanceof \Rbs\Geo\Address\AddressInterface)
			{
				if (!$this->checkMatchingAddress($defaultAddress,$matchingZone, $geoManager))
				{
					$defaultAddress = null;
				}
			}
			$defaultFieldValuesFor[$for] = ($defaultAddress instanceof \Rbs\Geo\Address\AddressInterface) ? $defaultAddress->toArray() : null;
		}

		/* @var $address \Rbs\Geo\Address\AddressInterface */
		foreach ($geoManager->getAddresses() as $address)
		{
			if (!$this->checkMatchingAddress($address, $matchingZone, $geoManager))
			{
				continue;
			}

			$addressData = $address->toArray();
			$default = [];
			foreach ($defaultFieldValuesFor as $for => $defaultFieldValues)
			{
				$default[$for] = ($defaultFieldValues == $addressData);
			}
			$addressData['default'] = $default;
			$addressesData[] = $addressData;
		}

		$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Geo/Address/', $addressesData);
		$result->setPaginationCount(count($addressesData));
		$event->setResult($result);
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
			return $match;
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

	/**
	 * Default actionPath: Rbs/Geo/Address/[addressId]
	 * Event params:
	 * @param \Change\Http\Event $event
	 */
	public function getAddress(\Change\Http\Event $event)
	{
		$addressId = $event->getParam('addressId');
		/** @var \Rbs\Geo\Documents\Address $address */
		$address = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($addressId, 'Rbs_Geo_Address');
		if ($address &&
			$address->getOwnerId() == $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->getId()) {
			/** @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$geoManager = $genericServices->getGeoManager();
			$addressData = $address->toArray();
			foreach ($geoManager->getDefaultForNames() as $default)
			{
				$def = $geoManager->getDefaultAddress($default);
				$addressData['default'][$default] = ($address === $def);
			}
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Geo/Address', $addressData);
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Geo/Address/
	 * Event params:
	 *  data:
	 *   common
	 *   fields
	 *   default
	 * @param \Change\Http\Event $event
	 */
	public function addAddress(\Change\Http\Event $event)
	{
		$addressData = $event->getParam('data');
		if (!is_array($addressData))
		{
			return;
		}
		if (isset($addressData['common']['name']))
		{
			/** @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$geoManager = $genericServices->getGeoManager();
			$address = $geoManager->addAddress($addressData);
			if ($address instanceof \Rbs\Geo\Documents\Address) {
				$event->setParam('addressId', $address->getId());
				$this->getAddress($event);
			} elseif ($address) {
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Geo/Address', $address->toArray());
				$event->setResult($result);
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Geo/Address/
	 * Event params:
	 *  addressId
	 *  data:
	 *   common
	 *   fields
	 *   default
	 * @param \Change\Http\Event $event
	 */
	public function updateAddress(\Change\Http\Event $event)
	{
		$addressData = $event->getParam('data');
		if (!is_array($addressData))
		{
			return;
		}
		$addressId = $event->getParam('addressId');
		/** @var \Rbs\Geo\Documents\Address $address */
		$address = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($addressId, 'Rbs_Geo_Address');
		if ($address &&
			$address->getOwnerId() == $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->getId()) {
			if ($addressData && $addressData['common']['id'] == $addressId)
			{
				/** @var \Rbs\Generic\GenericServices $genericServices */
				$genericServices = $event->getServices('genericServices');
				$geoManager = $genericServices->getGeoManager();
				$updatedAddress = $geoManager->updateAddress($addressData);
				if ($updatedAddress) {
					$this->getAddress($event);
				}
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Geo/Address/
	 * Event params:
	 *  addressId
	 *  data:
	 * @param \Change\Http\Event $event
	 */
	public function deleteAddress(\Change\Http\Event $event)
	{
		$addressId = intval($event->getParam('addressId'));

		/** @var \Rbs\Geo\Documents\Address $address */
		$address = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($addressId, 'Rbs_Geo_Address');
		if ($address &&
			$address->getOwnerId() == $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->getId())
		{
			/** @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$geoManager = $genericServices->getGeoManager();
			$deleted = $geoManager->deleteAddress($address);
			if ($deleted) {
				$addressData= ['common' => ['id' => $addressId, 'deleted'=> true]];
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Geo/Address', $addressData);
				$event->setResult($result);
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Geo/ValidateAddress
	 * Event params:
	 *  data:
	 *   address
	 *   matchingZone
	 *   compatibleZones
	 * @param \Change\Http\Event $event
	 */
	public function validateAddress(\Change\Http\Event $event)
	{
		$data = $event->getParam('data');
		if (!is_array($data))
		{
			return;
		}
		$addressData = isset($data['address']) ? $data['address'] : null;
		$matchingZone = isset($data['matchingZone']) ? $data['matchingZone'] : null;
		$compatibleZones = isset($data['compatibleZones']) ? $data['compatibleZones'] : null;

		if (isset($addressData['common']['addressFieldsId']))
		{
			/** @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$geoManager = $genericServices->getGeoManager();
			$address = $geoManager->validateAddress($addressData);
			if ($address)
			{
				if ($this->checkMatchingAddress($address, $matchingZone, $geoManager))
				{
					$addressData = $address->toArray();
					if (is_array($compatibleZones) && count($compatibleZones))
					{
						$addressData['compatibleZones'] = [];
						foreach ($compatibleZones as $compatibleZone)
						{
							if (is_string($compatibleZone))
							{
								if ($this->checkMatchingAddress($address, $compatibleZone, $geoManager))
								{
									$addressData['compatibleZones'][] = $compatibleZone;
								}
							}
						}

						if (!count($addressData['compatibleZones']))
						{
							$error = $event->getApplicationServices()->getI18nManager()->trans('m.rbs.geo.front.no_compatible_zone');
							$result = new \Change\Http\Ajax\V1\ErrorResult('compatibleZones', $error, \Zend\Http\Response::STATUS_CODE_409);
							$event->setResult($result);
							return;
						}
					}
					$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Geo/ValidateAddress', $addressData);
					$event->setResult($result);
				}
				else
				{
					$result = new \Change\Http\Ajax\V1\ErrorResult('matchingZone', $geoManager->getLastValidationError(), \Zend\Http\Response::STATUS_CODE_409);
					$event->setResult($result);
					return;
				}
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Geo/AddressFieldsCountries/
	 * Event params:
	 *  data:
	 *   zoneCode
	 * @param \Change\Http\Event $event
	 */
	public function getAddressFieldsCountries(\Change\Http\Event $event)
	{
		$addressFieldsCountriesData = [];
		$data = $event->getParam('data');
		if (!is_array($data))
		{
			$data = [];
		}
		$zoneCode = isset($data['zoneCode']) ? $data['zoneCode'] : null;

		$genericServices = $event->getServices('genericServices');
		if ($genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$i18n = $event->getApplicationServices()->getI18nManager();
			foreach ($genericServices->getGeoManager()->getCountriesByZoneCode($zoneCode) as $country)
			{
				// Exclude countries without defined address model.
				if (!$country->getAddressFields())
				{
					continue;
				}
				$addressFieldsCountryData = ['common' =>
					['id' => $country->getId(),
						'code' => $country->getCode(),
						'title' => $i18n->trans($country->getI18nTitleKey()),
						'addressFieldsId' => $country->getAddressFieldsId()
					]
				];
				$addressFieldsCountriesData[] = $addressFieldsCountryData;
			}
		}

		$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Geo/AddressFieldsCountries/', $addressFieldsCountriesData);
		$result->setPaginationCount(count($addressFieldsCountriesData));
		$event->setResult($result);
	}

	/**
	 * Default actionPath: Rbs/Geo/CityAutoCompletion/
	 * Event params:
	 *  - data:
	 *    - beginOfName
	 *    - countryCode
	 *    - options:
	 *       - modeId
	 * @param \Change\Http\Event $event
	 */
	public function cityAutoCompletion(\Change\Http\Event $event)
	{
		$genericServices = $event->getServices('genericServices');
		if ($genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$context = $event->paramsToArray();
			$autoCompletionData = $genericServices->getGeoManager()->getCityAutoCompletion($context);
			$result = new \Change\Http\Ajax\V1\ItemsResult('Rbs/Geo/CityAutoCompletion/', $autoCompletionData);
			$result->setPaginationCount(count($autoCompletionData));
			$event->setResult($result);
		}
	}

	/**
	 * Default actionPath: Rbs/Geo/AddressFields/{addressFieldsId}
	 * Event params:
	 *  - addressFieldsId
	 * @param \Change\Http\Event $event
	 */
	public function getAddressFieldsData(\Change\Http\Event $event)
	{
		/** @var $addressFields \Rbs\Geo\Documents\AddressFields */
		$addressFields = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('addressFieldsId'), 'Rbs_Geo_AddressFields');

		/** @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');

		if ($addressFields && $genericServices)
		{
			$event->setParam('detailed', true);
			$context = $event->paramsToArray();
			$addressFieldsData = $genericServices->getGeoManager()->getAddressFieldsData($addressFields, $context);
			$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Catalog/Product', $addressFieldsData);
			$event->setResult($result);
		}
	}
}