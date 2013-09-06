<?php
namespace Rbs\Order\Documents;

/**
 * @name \Rbs\Order\Documents\Order
 */
class Order extends \Compilation\Rbs\Order\Documents\Order
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getCode() ? $this->getCode() : '[' . $this->getId() . ']';
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
