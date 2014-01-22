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