<?php
namespace Rbs\Order\Documents;

/**
 * @name \Rbs\Order\Documents\CreditNote
 */
class CreditNote extends \Compilation\Rbs\Order\Documents\CreditNote
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
