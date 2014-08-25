<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Filters;

/**
* @name \Rbs\Commerce\Filters\Filters
*/
class Filters implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'CommerceFilters';

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
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Commerce/Filters');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getDefinitions', [$this, 'onDefaultGetDefinitions'], 5);

		$eventManager->attach(['isValidLinesAmountValue', 'isValidLinesPriceValue'], [$this, 'onDefaultIsValidLinesAmountValue'], 5);
		$eventManager->attach(['isValidTotalAmountValue', 'isValidTotalPriceValue'], [$this, 'onDefaultIsValidTotalAmountValue'], 5);
		$eventManager->attach('isValidPaymentAmountValue', [$this, 'onDefaultIsValidPaymentAmountValue'], 5);
		$eventManager->attach('isValidHasCoupon', [$this, 'onDefaultIsValidHasCoupon'], 5);
		$eventManager->attach('isValidHasCreditNote', [$this, 'onDefaultIsValidHasCreditNote'], 5);
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
			$filtersDefinition[] = $definition;
		}
		$event->setParam('filtersDefinition', $filtersDefinition);
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart|\Rbs\Order\Documents\Order $value
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
	 * @param \Rbs\Commerce\Cart\Cart|\Rbs\Order\Documents\Order $value
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
	public function onDefaultIsValidLinesAmountValue($event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'] + ['operator' => 'isNull', 'value' => null];
			$expected = $parameters['value'];
			$operator = $parameters['operator'];

			$value = $event->getParam('value');
			if ($value instanceof \Rbs\Commerce\Cart\Cart)
			{
				$amount = $value->getPricesValueWithTax() ? $value->getLinesAmountWithTaxes() : $value->getLinesAmount();
				$event->setParam('valid', $this->testNumValue($amount, $operator, $expected));
			}
			elseif ($value instanceof \Rbs\Order\Documents\Order)
			{
				$amount = $value->getPricesValueWithTax() ? $value->getLinesAmountWithTaxes() : $value->getLinesAmount();
				$event->setParam('valid', $this->testNumValue($amount, $operator, $expected));
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidTotalAmountValue($event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'] + ['operator' => 'isNull', 'value' => null];
			$expected = $parameters['value'];
			$operator = $parameters['operator'];

			$value = $event->getParam('value');
			if ($value instanceof \Rbs\Commerce\Cart\Cart)
			{
				$amount = $value->getPricesValueWithTax() ? $value->getTotalAmountWithTaxes() : $value->getTotalAmount();
				$event->setParam('valid', $this->testNumValue($amount, $operator, $expected));
			}
			elseif ($value instanceof \Rbs\Order\Documents\Order)
			{
				$amount = $value->getPricesValueWithTax() ? $value->getTotalAmountWithTaxes() : $value->getTotalAmount();
				$event->setParam('valid', $this->testNumValue($amount, $operator, $expected));
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidPaymentAmountValue($event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'] + ['operator' => 'isNull', 'value' => null];
			$expected = $parameters['value'];
			$operator = $parameters['operator'];

			$value = $event->getParam('value');
			if ($value instanceof \Rbs\Commerce\Cart\Cart)
			{
				$amount = $value->getPaymentAmountWithTaxes();
				$event->setParam('valid', $this->testNumValue($amount, $operator, $expected));
			}
			elseif ($value instanceof \Rbs\Order\Documents\Order)
			{
				$amount =  $value->getPaymentAmountWithTaxes();
				$event->setParam('valid', $this->testNumValue($amount, $operator, $expected));
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidHasCreditNote($event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$value = $event->getParam('value');
			if ($value instanceof \Rbs\Commerce\Cart\Cart)
			{
				/** @var $creditNotes \Rbs\Commerce\Process\BaseCreditNote[] */
				$creditNotes = $value->getCreditNotes();
				$amount = null;
				if (is_array($creditNotes) && count($creditNotes))
				{
					$amount = 0.0;
					foreach ($creditNotes as $creditNote)
					{
						$amount += abs($creditNote->getAmount());
					}
				}
				$event->setParam('valid', $amount !== null && $amount > 0.0);
			}
			elseif ($value instanceof \Rbs\Order\Documents\Order)
			{
				/** @var $creditNotes \Rbs\Commerce\Process\BaseCreditNote[] */
				$creditNotes = $value->getCreditNotes();
				$amount = null;
				if (is_array($creditNotes) && count($creditNotes))
				{
					$amount = 0.0;
					foreach ($creditNotes as $creditNote)
					{
						$amount += abs($creditNote->getAmount());
					}
				}
				$event->setParam('valid', $amount !== null && $amount > 0.0);
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultIsValidHasCoupon($event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'] + ['operator' => 'isNull', 'value' => null];
			$expected = $parameters['value'];
			$operator = $parameters['operator'];
			$valid = null;
			$value = $event->getParam('value');
			$coupons = [];
			if ($value instanceof \Rbs\Commerce\Cart\Cart)
			{
				$coupons = $value->getCoupons();
			}
			elseif ($value instanceof \Rbs\Order\Documents\Order)
			{
				$coupons = $value->getCoupons();
			}


			if ($operator === 'isNull')
			{
				$valid = (count($coupons) === 0);
			}
			elseif ($operator === 'eq')
			{
				$valid = false;
				foreach ($coupons as $coupon)
				{
					if ($coupon->getOptions()->get('id') == $expected)
					{
						$valid = true;
						break;
					}
				}
			}
			elseif ($operator === 'neq')
			{
				$valid = true;
				foreach ($coupons as $coupon)
				{
					if ($coupon->getOptions()->get('id') == $expected)
					{
						$valid = false;
						break;
					}
				}
			}

			if ($valid !== null)
			{
				$event->setParam('valid', $valid);
			}
		}
	}

	/**
	 * @param $value
	 * @param $operator
	 * @param $expeted
	 * @return boolean
	 */
	protected function testNumValue($value, $operator, $expeted)
	{
		switch ($operator)
		{
			case 'eq':
				return abs($value - $expeted) < 0.0001;
			case 'neq':
				return abs($value - $expeted) > 0.0001;
			case 'lte':
				return $value <= $expeted;
			case 'gte':
				return $value >= $expeted;
			case 'isNull':
				return $value === null;
		}
		return false;
	}
} 