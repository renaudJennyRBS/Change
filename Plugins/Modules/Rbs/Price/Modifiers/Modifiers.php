<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Price\Modifiers;

use Change\I18n\I18nString;

/**
* @name \Rbs\Price\Modifiers\Modifiers
*/
class Modifiers
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addModifierNames(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$i18nManager = $applicationServices->getI18nManager();
		$collection = array(
			'rbs-price-modifier-reservation-quantity' => new I18nString($i18nManager, 'm.rbs.price.admin.modifiers_reservation_quantity_title', array('ucf')),
			'rbs-price-modifier-lines-amount' => new I18nString($i18nManager, 'm.rbs.price.admin.modifiers_lines_amount_title', array('ucf'))
		);
		$collection = new \Change\Collection\CollectionArray('Rbs_Price_Collection_ModifierNames', $collection);
		$event->setParam('collection', $collection);
	}

	/**
	 * Event Param: quantity, line, lineItem
	 * @param \Change\Events\Event $event
	 */
	public function onPriceModifierReservationQuantity(\Change\Events\Event $event)
	{
		$price = $event->getTarget();
		if ($price instanceof \Rbs\Price\Documents\Price)
		{
			$options = $price->getOptions();
			if (isset($options['thresholds']) && is_array($options['thresholds']) && count($options['thresholds']))
			{
				$quantity = $event->getParam('quantity', 1);
				$line = $event->getParam('line');
				if ($line instanceof \Rbs\Commerce\Interfaces\LineInterface)
				{
					$quantity =  $line->getQuantity();
				}

				$lineItem = $event->getParam('lineItem');
				if ($lineItem instanceof \Rbs\Commerce\Interfaces\LineItemInterface)
				{
					$quantity *= $lineItem->getReservationQuantity();
				}

				if ($quantity > 1)
				{
					$value = false;
					foreach ($options['thresholds'] as $threshold)
					{
						if (isset($threshold['l']) && isset($threshold['v']) && $quantity >= $threshold['l'])
						{
								$value = $threshold['v'];
						}
					}
					if ($value !== false)
					{
						$event->setParam('contextualValue', $value);
					}
				}
			}
		}
	}

	/**
	 * Event Param: cart, order
	 * @param \Change\Events\Event $event
	 */
	public function onPriceModifierLinesAmount(\Change\Events\Event $event)
	{
		$price = $event->getTarget();
		if ($price instanceof \Rbs\Price\Documents\Price)
		{
			$options = $price->getOptions();
			if (isset($options['thresholds']) && is_array($options['thresholds']) && count($options['thresholds']))
			{

				$container = $event->getParam('cart', $event->getParam('order'));
				if ($container instanceof \Rbs\Commerce\Cart\Cart)
				{
					$amount = ($container->getPricesValueWithTax()) ? $container->getLinesAmountWithTaxes() : $container->getLinesAmountWithoutTaxes();
				}
				elseif ($container instanceof \Rbs\Order\Documents\Order)
				{
					$amount = ($container->getPricesValueWithTax()) ? $container->getLinesAmountWithTaxes() : $container->getLinesAmountWithoutTaxes();
				}
				else
				{
					$amount = null;
				}

				if ($amount !== null)
				{
					$value = false;
					foreach ($options['thresholds'] as $threshold)
					{
						if (isset($threshold['l']) && isset($threshold['v']) && $amount >= $threshold['l'])
						{
							$value = $threshold['v'];
						}
					}
					if ($value !== false)
					{
						$event->setParam('contextualValue', $value);
					}
				}
			}
		}
	}
} 