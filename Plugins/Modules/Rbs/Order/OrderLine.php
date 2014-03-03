<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order;

use Rbs\Commerce\Interfaces\LineInterface;

/**
 * @name \Rbs\Order\OrderLine
 * @method \Rbs\Order\OrderLineItem[] getItems()
 */
class OrderLine extends \Rbs\Commerce\Std\BaseLine implements LineInterface
{
	/**
	 * @param array $itemArray
	 * @return \Rbs\Order\OrderLineItem
	 */
	protected function getNewItemFromArray($itemArray)
	{
		return new OrderLineItem($itemArray);
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineItemInterface $lineItem
	 * @return \Rbs\Order\OrderLineItem
	 */
	protected function getNewItemFromLineItem($lineItem)
	{
		return new OrderLineItem($lineItem);
	}

	/**
	 * @param $codeSKU
	 * @return null|\Rbs\Order\OrderLineItem
	 */
	public function getOrderItemByCodeSky($codeSKU)
	{
		foreach ($this->getItems() as $item)
		{
			if ($item->getCodeSKU() === $codeSKU)
			{
				return $item;
			}
		}
		return null;
	}
}