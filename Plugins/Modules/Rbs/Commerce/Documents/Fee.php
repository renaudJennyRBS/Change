<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Documents;

/**
 * @name \Rbs\Commerce\Documents\Fee
 */
class Fee extends \Compilation\Rbs\Commerce\Documents\Fee
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('getValidModifier', [$this, 'onDefaultGetValidModifier'], 5);
	}

	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/** @var $fee Fee */
		$fee = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$restResult->setProperty('orderProcessId', $fee->getOrderProcessId());
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$i18n = $event->getApplicationServices()->getI18nManager();
			$restResult->setProperty('modelName', $i18n->trans($fee->getDocumentModel()->getLabelKey(), ['ucf']));
			$restResult->setProperty('orderProcessId', $fee->getOrderProcessId());
		}
	}

	/**
	 * @param mixed $value
	 * @param array $options
	 * @return \Rbs\Commerce\Process\ModifierInterface|null
	 */
	public function getValidModifier($value, array $options = null)
	{
		$args = ['value' => $value];
		if (is_array($options))
		{
			$args = array_merge($options, $args);
		}
		$event = new \Change\Documents\Events\Event('getValidModifier', $this, $args);
		$this->getEventManager()->trigger($event);
		$modifier = $event->getParam('modifier');
		if ($modifier instanceof \Rbs\Commerce\Process\ModifierInterface)
		{
			return $modifier;
		}
		return null;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultGetValidModifier(\Change\Documents\Events\Event $event)
	{
		/** @var $fee Fee */
		$fee = $event->getDocument();
		if (!($fee instanceof Fee) || !$fee->activated() || !$fee->getSku())
		{
			//Invalid Fee
			return;
		}

		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			return;
		}

		$value = $event->getParam('value');
		if ($value instanceof \Rbs\Commerce\Cart\Cart)
		{
			$shippingMode = $event->getParam('shippingMode');
			if ($shippingMode instanceof \Rbs\Shipping\Documents\Mode)
			{
				if ($fee->getShippingModeId() != $shippingMode->getId())
				{
					//Invalid compatible shipping mode
					return;
				}
			}
			elseif ($fee->getShippingModeId())
			{
				$ok = false;
				foreach ($value->getShippingModes() as $mode)
				{
					if ($mode->getId() == $fee->getShippingModeId())
					{
						$ok = true;
						break;
					}
				}
				if (!$ok)
				{
					//Invalid fee shipping mode
					return;
				}
			}

			$priceManager = $commerceServices->getPriceManager();
			$price = $commerceServices->getPriceManager()->getPriceBySku($fee->getSku(),
				['webStore' => $value->getWebStoreId(), 'billingArea' => $value->getBillingArea(), 'cart' => $value,
					'fee' => $fee]);
			if (!$price)
			{
				//fee has no price
				return;
			}

			if ($value->getCartManager()->isValidFilter($value, $fee->getCartFilterData()))
			{
				$event->setParam('modifier', new \Rbs\Commerce\Cart\CartFeeModifier($fee, $value, $price, $priceManager));
			}
		}
	}
}