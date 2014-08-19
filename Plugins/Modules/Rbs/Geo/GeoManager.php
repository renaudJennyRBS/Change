<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
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
		$eventManager->attach(static::EVENT_COUNTRIES_BY_ZONE_CODE, [$this, 'onDefaultGetCountriesByZoneCode'], 5);
		$eventManager->attach(static::EVENT_FORMAT_ADDRESS, [$this, 'onDefaultFormatAddress'], 5);
		$eventManager->attach('getAddresses', [$this, 'onDefaultGetAddresses'], 5);
		$eventManager->attach('deleteAddress', [$this, 'onDefaultDeleteAddress'], 5);
		$eventManager->attach('addAddress', [$this, 'onDefaultAddAddress'], 5);
		$eventManager->attach('updateAddress', [$this, 'onDefaultUpdateAddress'], 5);
		$eventManager->attach('setDefaultAddress', [$this, 'onDefaultSetDefaultAddress'], 5);
		$eventManager->attach('getDefaultAddress', [$this, 'onDefaultGetDefaultAddress'], 5);
		$eventManager->attach('getZoneByCode', [$this, 'onDefaultGetZoneByCode'], 5);

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
	 * @param string|null $zoneCode
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

		$documentManager = $event->getApplicationServices()->getDocumentManager();
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
					$i18n = $event->getApplicationServices()->getI18nManager();
					$fields['country'] = $i18n->trans($country->getI18nTitleKey());
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
	public function formatFieldsByLayout(array $fields, array $layout)
	{
		$lines =  array();
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
	 * Address creation from front office.
	 * @api
	 * @param array $fieldValues
	 * @param string $addressName
	 * @return boolean
	 */
	public function addAddress($fieldValues, $addressName)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['fieldValues' => $fieldValues, 'addressName' => $addressName]);
		$this->getEventManager()->trigger('addAddress', $this, $args);
		return (isset($args['done']) && $args['done'] === true);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultAddAddress($event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if (!$user->authenticated())
		{
			return;
		}

		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$fieldValues = is_array($event->getParam('fieldValues')) ? $event->getParam('fieldValues') : array();
			$addressFieldsId = isset($fieldValues['__addressFieldsId']) ? $fieldValues['__addressFieldsId'] : 0;
			/* @var $addressFields \Rbs\Geo\Documents\AddressFields */
			$addressFields = $documentManager->getDocumentInstance($addressFieldsId);

			/* @var $address \Rbs\Geo\Documents\Address */
			$address = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_Address');
			$address->setFieldValues($fieldValues);
			$address->setAddressFields($addressFields);
			$address->setOwnerId($user->getId());
			$address->setName(is_string($event->getParam('addressName')) ? $event->getParam('addressName') : '-');
			$address->save();

			$tm->commit();

			// Check if an default address is set
			$defaultAddress = $this->getDefaultAddress();
			if ($defaultAddress == null)
			{
				$this->setDefaultAddress($address);
			}
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$event->setParam('done', true);
	}

	/**
	 * Address creation from front office.
	 * @api
	 * @param array $fieldValues
	 * @param string $addressName
	 * @return boolean
	 */
	public function updateAddress($fieldValues, $addressName)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['fieldValues' => $fieldValues, 'addressName' => $addressName]);
		$this->getEventManager()->trigger('updateAddress', $this, $args);
		return (isset($args['done']) && $args['done'] === true);
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

		/* @var $address \Rbs\Geo\Documents\Address */
		$fieldValues = $event->getParam('fieldValues');
		$addressId = isset($fieldValues['__id']) ? intval($fieldValues['__id']) : 0;
		$address = $documentManager->getDocumentInstance($addressId);
		if (!($address instanceof \Rbs\Geo\Documents\Address))
		{
			return;
		}

		if ($address->getOwnerId() != $user->getId())
		{
			return;
		}

		// If the addressId represents an address document owned by the current user, delete it.
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$address->setFieldValues($fieldValues);
			$address->setName($event->getParam('addressName'));
			$address->save();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$event->setParam('done', true);
	}

	/**
	 * Set default address from front office.
	 * @api
	 * @param mixed $address
	 * @return boolean
	 */
	public function setDefaultAddress($address)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['address' => $address]);
		$this->getEventManager()->trigger('setDefaultAddress', $this, $args);
		return (isset($args['done']) && $args['done'] === true);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultSetDefaultAddress($event)
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

		$user = $documentManager->getDocumentInstance($user->getId());
		if (!($user instanceof \Rbs\User\Documents\User))
		{
			return;
		}

		// If the addressId represents an address document and there is an authenticated user, set the default address meta.
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$user->setMeta('Rbs_Geo_DefaultAddressId', $address->getId());
			$user->saveMetas();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$event->setParam('done', true);
	}

	/**
	 * Get default address for front office.
	 * @api
	 * @return \Rbs\Geo\Address\AddressInterface|null
	 */
	public function getDefaultAddress()
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs([]);
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
			return $this->addressFilters->isValid($address, $zone->getAddressFilterData(), $options);
		}
		elseif (is_null($zone))
		{
			return true;
		}
		return false;

	}
}