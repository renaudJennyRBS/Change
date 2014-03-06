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
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_COUNTRIES_BY_ZONE_CODE, [$this, 'onDefaultGetCountriesByZoneCode'], 5);
		$eventManager->attach(static::EVENT_FORMAT_ADDRESS, [$this, 'onDefaultFormatAddress'], 5);
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

		// If a zone code is specified, look for a country having this code or a country with a zone on it having this code.
		if ($zoneCode)
		{
			$query = $documentManager->getNewQuery('Rbs_Geo_Country');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->eq('code', $zoneCode), $pb->activated());
			$country = $query->getFirstDocument();
			if ($country)
			{
				$event->setParam('countries', array($country));
				return;
			}

			$query = $documentManager->getNewQuery('Rbs_Geo_Country');
			$pb = $query->getPredicateBuilder();
			$query->andPredicates($pb->activated());
			$d2qb = $query->getModelBuilder('Rbs_Geo_Zone', 'country');
			$query->andPredicates($d2qb->eq('code', $zoneCode));
			$country = $query->getFirstDocument();
			if ($country)
			{
				$event->setParam('countries', array($country));
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
		$addressFields = null;
		$fields = $address->getFields();

		if ($address instanceof \Rbs\Geo\Documents\Address)
		{
			$addressFields = $address->getAddressFields();
		}
		elseif (isset($fields['__addressFieldsId']))
		{
			$addressFields = $documentManager->getDocumentInstance($fields['__addressFieldsId'], 'Rbs_Geo_AddressFields');
		}

		$layout = null;
		if ($addressFields instanceof  \Rbs\Geo\Documents\AddressFields)
		{
			$layout = $addressFields->getFieldsLayoutData();
		}

		if (count($fields) == 0 || !is_array($layout) || count($layout) == 0)
		{
			if ($address instanceof \Rbs\Geo\Address\BaseAddress)
			{
				$event->setParam('lines', $address->getLines());
			}
			return;
		}

		if (!isset($fields['country']) && isset($fields[AddressInterface::COUNTRY_CODE_FIELD_NAME]))
		{
			$countryCode = $fields[AddressInterface::COUNTRY_CODE_FIELD_NAME];
			$dqb = $documentManager->getNewQuery('Rbs_Geo_Country');
			$dqb->andPredicates($dqb->eq('code', $countryCode));
			$country = $dqb->getFirstDocument();
			if ($country instanceof \Rbs\Geo\Documents\Country)
			{
				$i18n = $event->getApplicationServices()->getI18nManager();
				$fields['country'] =  $i18n->trans($country->getI18nTitleKey());
			}
		}

		$event->setParam('lines', $this->formatFieldsByLayout($fields, $layout));
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
}