<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo;

use Rbs\Geo\Address\AddressInterface;

/**
 * @name \Rbs\Geo\GeoManager
 */
class GeoManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Geo_GeoManager';
	const EVENT_COUNTRIES_BY_ZONE_CODE = 'getCountriesByZoneCode';
	const EVENT_FORMAT_ADDRESS = 'formatAddress';

	/**
	 * @var \Rbs\Geo\Address\AddressFilters
	 */
	protected $addressFilters;

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getDefaultForNames', [$this, 'onDefaultGetDefaultForNames'], 5);
		$eventManager->attach(static::EVENT_COUNTRIES_BY_ZONE_CODE, [$this, 'onDefaultGetCountriesByZoneCode'], 5);
		$eventManager->attach(static::EVENT_FORMAT_ADDRESS, [$this, 'onDefaultFormatAddress'], 5);
		$eventManager->attach('getAddresses', [$this, 'onDefaultGetAddresses'], 5);
		$eventManager->attach('deleteAddress', [$this, 'onDefaultDeleteAddress'], 5);

		$eventManager->attach('validateAddress', [$this, 'onDefaultValidateAddress'], 5);
		$eventManager->attach('addAddress', [$this, 'onDefaultAddAddress'], 5);

		$eventManager->attach('updateAddress', [$this, 'onDefaultUpdateAddress'], 5);
		$eventManager->attach('setDefaultAddress', [$this, 'onDefaultSetDefaultAddress'], 5);
		$eventManager->attach('getDefaultAddress', [$this, 'onDefaultGetDefaultAddress'], 5);
		$eventManager->attach('getZoneByCode', [$this, 'onDefaultGetZoneByCode'], 5);
		$eventManager->attach('getCityAutocompletion', [$this, 'onDefaultGetCityAutocompletion'], 1);
		$eventManager->attach('getPoints', [$this, 'onDefaultGetPoints'], 1);
		$eventManager->attach('getAddressFieldsData', [$this, 'onDefaultGetAddressFieldsData'], 5);


	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Geo/Events/GeoManager');
	}

	/**
	 * @param string|string[]|null $zoneCode
	 * @return \Rbs\Geo\Documents\Country[]
	 */
	public function getCountriesByZoneCode($zoneCode)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['zoneCode' => $zoneCode]);

		$this->getEventManager()->trigger('getCountriesByZoneCode', $this, $args);
		if (isset($args['countries']) && is_array($args['countries']))
		{
			return $args['countries'];
		}
		return array();
	}

	/**
	 * Event Params: zoneCode
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCountriesByZoneCode(\Change\Events\Event $event)
	{
		$zoneCode = $event->getParam('zoneCode');
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		if (($zoneCode && is_array($zoneCode) && count($zoneCode) == 0))
		{
			$zoneCode = null;
		}

		// If a zone code is specified, look for a country having this code or a country with a zone on it having this code.
		if ($zoneCode)
		{
			if (!is_array($zoneCode))
			{
				$zoneCode = [$zoneCode];
			}

			$queryZone = $documentManager->getNewQuery('Rbs_Geo_Zone');
			$queryZone->andPredicates($queryZone->in($queryZone->getColumn('code'), $zoneCode));
			$qZone = $queryZone->dbQueryBuilder()->query();
			$countriesIdZone = $qZone->getResults($qZone->getRowsConverter()->addIntCol('country'));

			$query = $documentManager->getNewQuery('Rbs_Geo_Country');
			$query->andPredicates($query->activated());
			if (count($countriesIdZone) > 0)
			{
				$query->andPredicates(
					$query->getPredicateBuilder()->logicOr(
						$query->in('code', $zoneCode),
						$query->in('id', $countriesIdZone)
					)
				);
			}
			else
			{
				$query->andPredicates($query->in('code', $zoneCode));
			}
			$countries = $query->getDocuments()->toArray();

			if (count($countries) > 0)
			{
				$event->setParam('countries', $countries);
				return;
			}
		}
		// If there is no zone code specified, look for all active countries.
		else
		{
			$query = $documentManager->getNewQuery('Rbs_Geo_Country');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->activated());
			$event->setParam('countries', $query->getDocuments()->toArray());
		}
	}

	/**
	 * @param AddressInterface $address
	 * @return string[]
	 */
	public function getFormattedAddress($address)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['address' => $address]);
		$this->getEventManager()->trigger(static::EVENT_FORMAT_ADDRESS, $this, $args);
		if (isset($args['lines']) && is_array($args['lines']))
		{
			return $args['lines'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultFormatAddress($event)
	{
		$address = $event->getParam('address');
		if (!($address instanceof AddressInterface))
		{
			return;
		}
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();
		$fields = $address->getFields();
		if (!is_array($fields) || !count($fields))
		{
			return;
		}

		$addressFields = null;
		if ($address instanceof \Rbs\Geo\Documents\Address)
		{
			$addressFields = $address->getAddressFields();
		}
		elseif (isset($fields['__addressFieldsId']))
		{
			$addressFields = $documentManager->getDocumentInstance($fields['__addressFieldsId'], 'Rbs_Geo_AddressFields');
		}

		if ($addressFields instanceof \Rbs\Geo\Documents\AddressFields)
		{
			if (!isset($fields['country']) && isset($fields[AddressInterface::COUNTRY_CODE_FIELD_NAME]))
			{
				$countryCode = $fields[AddressInterface::COUNTRY_CODE_FIELD_NAME];
				$dqb = $documentManager->getNewQuery('Rbs_Geo_Country');
				$dqb->andPredicates($dqb->eq('code', $countryCode));
				$country = $dqb->getFirstDocument();
				if ($country instanceof \Rbs\Geo\Documents\Country)
				{
					$i18n = $applicationServices->getI18nManager();
					$fields['country'] = $i18n->trans($country->getI18nTitleKey());
				}
			}
			$collectionManager = $applicationServices->getCollectionManager();
			foreach ($addressFields->getFields() as $fieldDefinition)
			{
				$fieldCode = $fieldDefinition->getCode();
				if ($fieldDefinition->getCollectionCode() && isset($fields[$fieldCode]))
				{
					$collection = $collectionManager->getCollection($fieldDefinition->getCollectionCode());
					if ($collection)
					{
						$value = $collection->getItemByValue($fields[$fieldCode]);
						if ($value)
						{
							$fields[$fieldCode] = $value->getTitle();
						}
					}
				}
			}
			$event->setParam('lines', $this->formatFieldsByLayout($fields, $addressFields->getFieldsLayout()));
		}
	}

	/**
	 * @param array $fields
	 * @param array $layout
	 * @return array
	 */
	protected function formatFieldsByLayout(array $fields, array $layout)
	{
		$lines = array();
		foreach ($layout as $lineLayout)
		{
			$line = [];
			foreach ($lineLayout as $fieldName)
			{
				$fieldValue = isset($fields[$fieldName]) ? $fields[$fieldName] : null;
				if ($fieldValue)
				{
					$line[] = $fieldValue;
				}
			}
			if (count($line))
			{
				$lines[] = implode(' ', $line);
			}
		}
		return $lines;
	}

	/**
	 * Get addresses to display in front office.
	 * @api
	 * @returns \Rbs\Geo\Address\AddressInterface[]
	 */
	public function getAddresses()
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs([]);
		$this->getEventManager()->trigger('getAddresses', $this, $args);
		if (isset($args['addresses']) && is_array($args['addresses']))
		{
			return $args['addresses'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetAddresses($event)
	{
		$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if (!$user->authenticated())
		{
			return;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$query = $documentManager->getNewQuery('Rbs_Geo_Address');
		$query->andPredicates($query->eq('ownerId', $user->getId()));
		$query->addOrder('name');
		$event->setParam('addresses', $query->getDocuments()->toArray());
	}

	/**
	 * Address deletion from front office.
	 * @api
	 * @param mixed $address
	 * @return boolean
	 */
	public function deleteAddress($address)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['address' => $address]);
		$this->getEventManager()->trigger('deleteAddress', $this, $args);
		return (isset($args['done']) && $args['done'] === true);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultDeleteAddress($event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$address = $event->getParam('address');
		if (is_numeric($address))
		{
			$address = $documentManager->getDocumentInstance(intval($address));
		}
		if (!($address instanceof \Rbs\Geo\Documents\Address))
		{
			return;
		}

		$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if (!$user->authenticated())
		{
			return;
		}

		if ($user->getId() != $address->getOwnerId())
		{
			return;
		}

		// If the addressId represents an address document it is owned by the current user, delete it.
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$address->delete();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$event->setParam('done', true);
	}

	/**
	 * get default for names
	 * ex: ['default', 'shipping', 'billing']
	 * @api
	 * @return string[]
	 */
	public function getDefaultForNames()
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['names' => []]);
		$this->getEventManager()->trigger('getDefaultForNames', $this, $args);
		return is_array($args['names']) ? $args['names'] : [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetDefaultForNames($event)
	{
		$names = $event->getParam('names');
		if (is_array($names) && !in_array('default', $names))
		{
			$names[] = 'default';
			$event->setParam('names', $names);
		}
	}


	/**
	 * Address creation from front office.
	 * @api
	 * @param array $addressData
	 * @return \Rbs\Geo\Address\AddressInterface|null
	 */
	public function validateAddress($addressData)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['addressData' => $addressData]);
		$eventManager->trigger('validateAddress', $this, $args);
		return (isset($args['address']) && $args['address'] instanceof \Rbs\Geo\Address\AddressInterface) ? $args['address'] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultValidateAddress($event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		$addressData = $event->getParam('addressData');
		$addressFieldsId = intval($addressData['common']['addressFieldsId']);

		/** @var \Rbs\Geo\Documents\AddressFields $addressFields */
		$addressFields = $documentManager->getDocumentInstance($addressFieldsId, 'Rbs_Geo_AddressFields');
		if ($addressFields) {

			$address = new \Rbs\Geo\Address\BaseAddress($addressData);
			$address->setFieldValue('__lines', $this->getFormattedAddress($address));
			$event->setParam('address', $address);
		}
	}

	/**
	 * Address creation from front office.
	 * @api
	 * @param array $addressData
	 * @return \Rbs\Geo\Address\AddressInterface|null
	 */
	public function addAddress($addressData)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['addressData' => $addressData]);
		$eventManager->trigger('addAddress', $this, $args);
		return (isset($args['address']) && $args['address'] instanceof \Rbs\Geo\Address\AddressInterface) ? $args['address'] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultAddAddress($event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		$addressData = $event->getParam('addressData');
		if (!$user->authenticated() || !is_array($addressData) ||
			!isset($addressData['common']['addressFieldsId']) || !isset($addressData['fields']['countryCode']))
		{
			return;
		}

		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$addressFieldsId = intval($addressData['common']['addressFieldsId']);
			$name = isset($addressData['common']['name']) ? $addressData['common']['name'] : '-';

			/* @var $addressFields \Rbs\Geo\Documents\AddressFields */
			$addressFields = $documentManager->getDocumentInstance($addressFieldsId, 'Rbs_Geo_AddressFields');
			$fieldValues = $addressData['fields'];

			/* @var $address \Rbs\Geo\Documents\Address */
			$address = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_Address');
			$address->setFieldValues($fieldValues);
			$address->setAddressFields($addressFields);
			$address->setOwnerId($user->getId());
			$address->setName($name);
			$address->save();

			$tm->commit();


		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		if (isset($addressData['default']) && is_array($addressData['default']))
		{
			$defaultFor = [];
			foreach ($addressData['default'] as $default => $isDefault)
			{
				if ($isDefault)
				{
					$defaultFor[] = $default;
				}
				$this->setDefaultAddress($address, $defaultFor);
			}
		}

		$event->setParam('address', $address);
	}

	/**
	 * Address creation from front office.
	 * @api
	 * @param array $addressData
	 * @return \Rbs\Geo\Address\AddressInterface|null
	 */
	public function updateAddress($addressData)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['addressData' => $addressData]);
		$this->getEventManager()->trigger('updateAddress', $this, $args);
		return (isset($args['address']) && $args['address'] instanceof \Rbs\Geo\Address\AddressInterface) ? $args['address'] : null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultUpdateAddress($event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if (!$user->authenticated())
		{
			return;
		}
		$addressData = $event->getParam('addressData');
		if (!is_array($addressData) || !isset($addressData['common']['id']))
		{
			return;
		}
		$addressId = intval($addressData['common']['id']);

		/* @var $address \Rbs\Geo\Documents\Address */
		$address = $documentManager->getDocumentInstance($addressId, 'Rbs_Geo_Address');
		if (!($address instanceof \Rbs\Geo\Documents\Address) || $address->getOwnerId() != $user->getId())
		{
			return;
		}

		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			if (isset($addressData['common']['addressFieldsId']))
			{
				$addressFieldsId =  intval($addressData['common']['addressFieldsId']);
				if ($addressFieldsId)
				{
					$addressFields = $documentManager->getDocumentInstance($addressFieldsId, 'Rbs_Geo_AddressFields');
					if ($addressFields instanceof \Rbs\Geo\Documents\AddressFields)
					{
						$address->setAddressFields($addressFields);
					}
				}
			}
			if (isset($addressData['fields'])) {
				$address->setFieldValues(is_array($addressData['fields']) ? $addressData['fields'] : []);
			}
			if (isset($addressData['common']['name']))
			{
				$addressName = strval($addressData['common']['name']);
				$address->setName(\Change\Stdlib\String::isEmpty($addressName) ? '-' : $addressName);
			}

			$address->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		if (isset($addressData['default']) && is_array($addressData['default']))
		{
			$defaultFor = [];
			foreach ($addressData['default'] as $default => $isDefault)
			{
				if ($isDefault)
				{
					$defaultFor[] = $default;
				}
				$this->setDefaultAddress($address, $defaultFor);
			}
		}

		$event->setParam('address', $address);
	}

	/**
	 * Set default address from front office.
	 * @api
	 * @param mixed $address
	 * @param string|array $defaultFor
	 * @return boolean
	 */
	public function setDefaultAddress($address, $defaultFor = [])
	{
		$eventManager = $this->getEventManager();
		if (is_string($defaultFor))
		{
			$defaultFor = [$defaultFor];
		}
		$args = $eventManager->prepareArgs(['address' => $address, 'defaultFor' => $defaultFor]);
		$this->getEventManager()->trigger('setDefaultAddress', $this, $args);
		return (isset($args['done']) && $args['done'] === true);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultSetDefaultAddress($event)
	{
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();
		$defaultFor = $event->getParam('defaultFor');

		$address = $event->getParam('address');
		if (is_numeric($address))
		{
			$address = $documentManager->getDocumentInstance(intval($address));
		}

		if (!($address instanceof \Rbs\Geo\Documents\Address))
		{
			return;
		}

		$user = $applicationServices->getAuthenticationManager()->getCurrentUser();
		if (!$user->authenticated())
		{
			return;
		}

		$userDocument = $documentManager->getDocumentInstance($user->getId());
		if (!($userDocument instanceof \Rbs\User\Documents\User))
		{
			return;
		}

		// If the addressId represents an address document and there is an authenticated user, set the default address meta.
		$tm = $applicationServices->getTransactionManager();
		try
		{
			$tm->begin();
			if (is_array($defaultFor) && in_array('default', $defaultFor))
			{
				$userDocument->setMeta('Rbs_Geo_DefaultAddressId', $address->getId());
			}

			$userProfile = $applicationServices->getProfileManager()->loadProfile($user, 'Rbs_User');
			if ($userProfile instanceof \Rbs\User\Profile\Profile)
			{
				$addressFieldsValue = $address->getFields();
				$update = false;
				foreach ($userProfile->getPropertyNames() as $propertyName)
				{
					$profileValue = $userProfile->getPropertyValue($propertyName);
					if (isset($addressFieldsValue[$propertyName]) && ($profileValue === null || $profileValue === ''))
					{
						$userProfile->setPropertyValue($propertyName, $addressFieldsValue[$propertyName]);
						$update = true;
					}
				}
				if ($update)
				{
					$applicationServices->getProfileManager()->saveProfile($user, $userProfile);
				}
			}
			$userDocument->saveMetas();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$event->setParam('done', true);
	}

	/**
	 * Get default addresses for front office.
	 * @api
	 * @param string|array $defaultFor
	 * @return \Rbs\Geo\Address\AddressInterface[]|null
	 */
	public function getDefaultAddresses($defaultFor = [])
	{
		if (is_string($defaultFor))
		{
			$defaultFor = [$defaultFor];
		}
		$addresses = [];
		if (is_array($defaultFor) && count($defaultFor))
		{
			foreach ($defaultFor as $for)
			{
				$address = $this->getDefaultAddress([$for]);
				$addresses[$for] = $address;
			}
		}
		return $addresses;
	}

	/**
	 * Get default address for front office.
	 * @api
	 * @param string|array $defaultFor
	 * @return \Rbs\Geo\Address\AddressInterface|null
	 */
	public function getDefaultAddress($defaultFor = [])
	{
		if (is_string($defaultFor))
		{
			$defaultFor = [$defaultFor];
		}

		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['defaultFor' => $defaultFor]);
		$this->getEventManager()->trigger('getDefaultAddress', $this, $args);
		if (isset($args['defaultAddress']) && $args['defaultAddress'] instanceof \Rbs\Geo\Address\AddressInterface)
		{
			return $args['defaultAddress'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetDefaultAddress($event)
	{
		if ($event->getParam('defaultAddress') instanceof \Rbs\Geo\Address\AddressInterface)
		{
			return;
		}
		$defaultFor = $event->getParam('defaultFor');
		if (!is_array($defaultFor) || !in_array('default', $defaultFor))
		{
			return;
		}

		$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if (!$user->authenticated())
		{
			return;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$user = $documentManager->getDocumentInstance($user->getId());
		if (!($user instanceof \Rbs\User\Documents\User))
		{
			return;
		}
		$address = $documentManager->getDocumentInstance($user->getMeta('Rbs_Geo_DefaultAddressId'));
		if ($address instanceof \Rbs\Geo\Documents\Address)
		{
			$event->setParam('defaultAddress', $address);
		}
	}

	/**
	 * @param string $codeZone
	 * @return \Rbs\Geo\Documents\Zone|null
	 */
	public function getZoneByCode($codeZone)
	{
		if (is_string($codeZone))
		{
			$eventManager = $this->getEventManager();
			$args = $eventManager->prepareArgs(['codeZone' => $codeZone]);
			$this->getEventManager()->trigger('getZoneByCode', $this, $args);
			if (isset($args['zone']) && $args['zone'] instanceof \Rbs\Geo\Documents\Zone)
			{
				return $args['zone'];
			}
		}
		return null;
	}

	/**
	 * @var array
	 */
	protected $zonesByCode = [];

	/**
	 * Input param codeZone
	 * Output param zone
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetZoneByCode($event)
	{
		$codeZone = $event->getParam('codeZone');
		if (is_string($codeZone))
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			if (isset($this->zonesByCode[$codeZone]))
			{
				$event->setParam('zone', $documentManager->getDocumentInstance($this->zonesByCode[$codeZone]));
				return;
			}
			$query = $documentManager->getNewQuery('Rbs_Geo_Zone');
			$query->andPredicates($query->eq('code', $codeZone));
			$zone = $query->getFirstDocument();
			if ($zone)
			{
				$this->zonesByCode[$codeZone] = $zone->getId();
				$event->setParam('zone', $documentManager->getDocumentInstance($this->zonesByCode[$codeZone]));
			}
		}
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getAddressFiltersDefinition($options = [])
	{
		if ($this->addressFilters === null)
		{
			$this->addressFilters = new \Rbs\Geo\Address\AddressFilters($this->getApplication());
		}
		return $this->addressFilters->getDefinitions($options);
	}

	/**
	 * @param \Rbs\Geo\Address\AddressInterface $address
	 * @param \Rbs\Geo\Documents\Zone|string $zone
	 * @param array $options
	 * @return boolean
	 */
	public function isValidAddressForZone(\Rbs\Geo\Address\AddressInterface $address, $zone, array $options = [])
	{
		if ($this->addressFilters === null)
		{
			$this->addressFilters = new \Rbs\Geo\Address\AddressFilters($this->getApplication());
		}
		$this->addressFilters->setError(null);
		if (is_string($zone))
		{
			$zone = $this->getZoneByCode($zone);
			if (!$zone)
			{
				return false;
			}
		}

		if ($zone instanceof \Rbs\Geo\Documents\Zone)
		{
			$options['zone'] = $zone;
			$valid = $this->addressFilters->isValid($address, $zone->getAddressFilterData(), $options);
			if (!$valid)
			{
				$error = $zone->getCurrentLocalization()->getFilterErrorMessage();
				if (\Change\Stdlib\String::isEmpty($error))
				{
					$error = 'm.rbs.geo.front.invalid_address_for_zone';
				}
				$this->addressFilters->setError($error);
			}
			return $valid;
		}
		elseif (is_null($zone))
		{
			return true;
		}
		return false;
	}

	/**
	 * @return null|string
	 */
	public function getLastValidationError()
	{
		if ($this->addressFilters !== null) {
			return $this->addressFilters->getError();
		}
		return null;
	}
	/**
	 * @param string $context
	 * @return mixed|null
	 */
	public function getCityAutocompletion($context)
	{
		if (is_array($context))
		{
			if (!is_array($context['options']))
			{
				$context['options'] = [];
			}

			$eventManager = $this->getEventManager();
			$args = $eventManager->prepareArgs(['context' => $context]);
			$this->getEventManager()->trigger('getCityAutocompletion', $this, $args);
			if (isset($args['cities']) && is_array($args['cities']))
			{
				return $args['cities'];
			}
		}
		return null;
	}


	/**
	 * Input param beginOfName
	 * Output param cities
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCityAutocompletion($event)
	{
		$cities = $event->getParam('cities', []);

		if (count($cities) == 0)
		{
			// TODO
			//$context = $event->getParam('context');
		}

		$event->setParam('cities', $cities);
	}

	/**
	 * @param array $context
	 * @return \Rbs\Geo\Map\Point[]|null
	 */
	public function getPoints($context)
	{
		if (is_array($context))
		{
			if (!is_array($context['options']))
			{
				$context['options'] = [];
			}

			$eventManager = $this->getEventManager();
			$args = $eventManager->prepareArgs(['context' => $context]);
			$this->getEventManager()->trigger('getPoints', $this, $args);
			if (isset($args['points']) && is_array($args['points']))
			{
				return $args['points'];
			}
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetPoints($event)
	{
		$points = $event->getParam('points', []);

		if (count($points) == 0)
		{
			// TODO
		}

		$event->setParam('points', $points);
	}

	/**
	 * @param \Rbs\Geo\Documents\AddressFields|integer $addressFields
	 * @param array $context
	 * @return array
	 */
	public function getAddressFieldsData($addressFields, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['addressFields' => $addressFields, 'context' => $context]);
		$em->trigger('getAddressFieldsData', $this, $eventArgs);
		if (isset($eventArgs['addressFieldsData']) && is_array($eventArgs['addressFieldsData']))
		{
			return $eventArgs['addressFieldsData'];
		}
		return [];
	}

	/**
	 * Input param addressFields, context
	 * Output param addressFieldsData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetAddressFieldsData($event)
	{
		$addressFields = $event->getParam('addressFields');

		if (is_numeric($addressFields))
		{
			$addressFields = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($addressFields, 'Rbs_Geo_AddressFields');
		}


		if ($addressFields instanceof \Rbs\Geo\Documents\AddressFields)
		{
			$collectionManager = $event->getApplicationServices()->getCollectionManager();
			$dataSets = ['common' => ['id' => $addressFields->getId()], 'fields' => []];
			$idPrefix = uniqid($addressFields->getId() . '_') . '_';
			foreach ($addressFields->getFields() as $addressField)
			{
				$input = array(
					'name' => $addressField->getCode(),
					'id' => $idPrefix . $addressField->getCode(),
					'title' => $addressField->getTitle(),
					'required' => $addressField->getRequired(),
					'match' => $addressField->getMatch(),
					'matchErrorMessage' => $addressField->getCurrentLocalization()->getMatchErrorMessage(),
					'defaultValue' => $addressField->getDefaultValue()
				);
				if ($addressField->getCollectionCode())
				{
					$collection = $collectionManager->getCollection($addressField->getCollectionCode());
					if ($collection)
					{
						$values = array();
						foreach ($collection->getItems() as $item)
						{
							$values[$item->getValue()] = array('value' => $item->getValue(), 'title' => $item->getTitle());
						}
						$input['values'] = $values;
					}
				}
				$dataSets['fieldsIndex'][$input['name']] = count($dataSets['fields']);
				$dataSets['fields'][] = $input;
			}
			$dataSets['fieldsLayoutData'] = $addressFields->getFieldsLayoutData();
			$event->setParam('addressFieldsData', $dataSets);
		}
	}
}