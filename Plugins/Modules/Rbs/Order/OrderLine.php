<?php
namespace Rbs\Order;

use Rbs\Commerce\Interfaces\LineInterface;

/**
 * @name \Rbs\Order\OrderLine
 */
class OrderLine extends \Rbs\Commerce\Std\BaseLine implements LineInterface
{

	/**
	 * @param array $itemArray
	 * @return \Rbs\Order\OrderLineItem|null
	 */
	protected function getNewItemFromArray($itemArray)
	{
		return new OrderLineItem($itemArray);
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\LineItemInterface $lineItem
	 * @return \Rbs\Order\OrderLineItem|null
	 */
	protected function getNewItemFromLineItem($lineItem)
	{
		return new OrderLineItem($lineItem);
	}
}