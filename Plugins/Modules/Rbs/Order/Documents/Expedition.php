<?php
namespace Rbs\Order\Documents;

/**
 * @name \Rbs\Order\Documents\Expedition
 */
class Expedition extends \Compilation\Rbs\Order\Documents\Expedition
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		$order = $this->getOrderIdInstance();
		if ($order)
		{
			return 'E-' . $order->getLabel();
		}
		//TODO what do we do?
		return 'E-NO_ORDER_DEFINED';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}
}
