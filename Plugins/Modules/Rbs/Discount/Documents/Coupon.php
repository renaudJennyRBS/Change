<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Discount\Documents;

/**
 * @name \Rbs\Discount\Documents\Coupon
 */
class Coupon extends \Compilation\Rbs\Discount\Documents\Coupon
{
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/** @var $coupon Coupon */
		$coupon = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$restResult->setProperty('orderProcessId', $coupon->getOrderProcessId());
		}
		elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$restResult->setProperty('orderProcessId', $coupon->getOrderProcessId());
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart|\Rbs\Order\Documents\Order $value
	 * @param array $options
	 * @return boolean
	 */
	public function isCompatibleWith($value, array $options = null)
	{
		if ($this->activated())
		{
			if ($value instanceof \Rbs\Commerce\Cart\Cart || $value instanceof \Rbs\Order\Documents\Order)
			{
				$filters = new \Rbs\Commerce\Filters\Filters($this->getApplication());
				return $filters->isValid($value, $this->getCartFilterData());
			}
		}
		return false;
	}
}