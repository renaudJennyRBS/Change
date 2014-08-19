<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Address;

/**
 * @name \Rbs\Geo\Address\AddressFilters
 */
class AddressFilters implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'AddressFilters';

	public function __construct(\Change\Application $application)
	{
		$this->setApplication($application);
	}

	/**
	 * @return string
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
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Geo/AddressFilters');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getDefinitions', [$this, 'onDefaultGetDefinitions'], 5);
		$eventManager->attach('isValidCountryCode', [$this, 'onDefaultIsValidCountryCode'], 5);
		$eventManager->attach('isValidZipCode', [$this, 'onDefaultIsValidZipCode'], 5);
		$eventManager->attach('isValidField', [$this, 'onDefaultIsValidField'], 5);
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getDefinitions($options = [])
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['filtersDefinition' => [], 'options' => $options]);
		$em->trigger('getDefinitions', $this, $args);
		return isset($args['filtersDefinition']) && is_array($args['filtersDefinition']) ? array_values($args['filtersDefinition']) : [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetDefinitions($event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$filtersDefinition = $event->getParam('filtersDefinition');
		$defaultsDefinitions = json_decode(file_get_contents(__DIR__ . '/Assets/filtersDefinition.json'), true);
		foreach ($defaultsDefinitions as $definition)
		{
			$definition['config']['group'] = $i18nManager->trans($definition['config']['group'], ['ucf']);
			$definition['config']['listLabel'] = $i18nManager->trans($definition['config']['listLabel'], ['ucf']);
			$definition['config']['label'] = $i18nManager->trans($definition['config']['label'], ['ucf']);
			switch($definition['name']) {
				case 'countryCode':
					$definition['config']['possibleValues'] = $this->getCountries($event);
					break;
				case 'zipCode':
					$definition['config']['possibleValues'] = $this->getZipCodes($event);
			}
			$filtersDefinition[] = $definition;
		}
		$event->setParam('filtersDefinition', $filtersDefinition);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return array
	 */
	protected function getZipCodes($event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$possibleValues = [];
		$possibleValues[] = ['label' => $i18nManager->trans('m.rbs.geo.admin.zone_fr_a'), 'value' => '^((0[1-9])|([1345678][0-9])|(9[0-5])|(2[1-9]))[0-9]{3}$'];
		$possibleValues[] = ['label' => $i18nManager->trans('m.rbs.geo.admin.zone_fr_b'), 'value' => '^20[0-9]{3}$'];
		$possibleValues[] = ['label' => $i18nManager->trans('m.rbs.geo.admin.zone_fr_c'), 'value' => '^97[1-6][0-9]{2}$'];
		$possibleValues[] = ['label' => $i18nManager->trans('m.rbs.geo.admin.zone_fr_d'), 'value' => '^98[4678][0-9]{2}$'];
		return $possibleValues;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return array
	 */
	protected function getCountries($event)
	{
		$possibleValues = [];
		$collection = $event->getApplicationServices()->getCollectionManager()->getCollection('Rbs_Geo_Collection_Countries');
		if ($collection)
		{
			foreach ($collection->getItems() as $item)
			{
				$possibleValues[] = ['label' => $item->getLabel(), 'value' => $item->getValue()];
			}
		}
		return $possibleValues;
	}

	/**
	 * @api
	 * @param \Rbs\Geo\Address\AddressInterface $value
	 * @param array $filter
	 * @param array $options
	 * @return boolean
	 */
	public function isValid($value, $filter, $options = [])
	{
		if (is_array($filter) && isset($filter['name']))
		{
			$name = $filter['name'];
			if ($name === 'group')
			{
				if (isset($filter['operator']) && isset($filter['filters']) && is_array($filter['filters']))
				{
					return $this->isValidGroupFilters($value, $filter['operator'], $filter['filters']);
				}
			}
			else
			{
				$em = $this->getEventManager();
				$args = $em->prepareArgs(['value' => $value, 'name' => $name, 'filter' => $filter, 'options' => $options]);
				$em->trigger('isValid' . ucfirst($name), $this, $args);
				if (isset($args['valid']))
				{
					return ($args['valid'] == true);
				}
			}
		}
		return true;
	}

	/**
	 * @param \Rbs\Geo\Address\AddressInterface $value
	 * @param string $operator
	 * @param array $filters
	 * @return boolean
	 */
	protected function isValidGroupFilters($value, $operator, $filters)
	{
		if (!count($filters))
		{
			return true;
		}
		if ($operator === 'OR')
		{
			foreach ($filters as $filter)
			{
				if ($this->isValid($value, $filter))
				{
					return true;
				}
			}
			return false;
		}
		else
		{
			foreach ($filters as $filter)
			{
				if (!$this->isValid($value, $filter))
				{
					return false;
				}
			}
			return true;
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidCountryCode($event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'] + ['operator' => null, 'value' => null];
			$expected = $parameters['value'];
			$operator = $parameters['operator'];

			$value = $event->getParam('value');
			if ($value instanceof \Rbs\Geo\Address\AddressInterface)
			{
				$countryCode = $value->getCountryCode();
				if ($operator == 'eq')
				{
					$event->setParam('valid', $countryCode == $expected);
				}
				elseif ($operator == 'neq')
				{
					$event->setParam('valid', $countryCode != $expected);
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidZipCode($event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'] + ['operator' => null, 'value' => null];
			$expected = $parameters['value'];
			$operator = $parameters['operator'];

			$value = $event->getParam('value');
			if ($value instanceof \Rbs\Geo\Address\AddressInterface)
			{
				$zipCode = $value->getZipCode();
				if ($operator == 'eq')
				{
					$event->setParam('valid', $zipCode == $expected);
				}
				elseif ($operator == 'neq')
				{
					$event->setParam('valid', $zipCode != $expected);
				}
				elseif ($operator == 'match' && is_string($expected) && strlen($expected))
				{
					$event->setParam('valid', preg_match('/' . $expected . '/', $zipCode));
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidField($event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'] + ['operator' => null, 'value' => null, 'fieldName' => null];
			$expected = $parameters['value'];
			$operator = $parameters['operator'];
			$fieldName = $parameters['fieldName'];

			$value = $event->getParam('value');
			if (is_string($fieldName) && $value instanceof \Rbs\Geo\Address\AddressInterface)
			{
				$fields = $value->getFields();
				if (is_array($fields) && isset($fields[$fieldName]))
				{
					$fieldValue = $fields[$fieldName];
					if ($operator == 'isNull')
					{
						$event->setParam('valid', is_null($fieldValue));
					}
					elseif ($operator == 'eq')
					{
						$event->setParam('valid', \Change\Stdlib\String::toLower($fieldValue) == \Change\Stdlib\String::toLower($expected));
					}
					elseif ($operator == 'neq')
					{
						$event->setParam('valid', \Change\Stdlib\String::toLower($fieldValue) != \Change\Stdlib\String::toLower($expected));
					}
					elseif ($operator == 'match' && is_string($expected) && strlen($expected))
					{
						$event->setParam('valid', preg_match('/' . $expected . '/', $fieldValue));
					}
				}
				else
				{
					$event->setParam('valid', $operator == 'isNull');
				}
			}
		}
	}
} 