<?php
namespace Rbs\Stock\Documents;

/**
 * @name \Rbs\Stock\Documents\InventoryEntry
 */
class InventoryEntry extends \Compilation\Rbs\Stock\Documents\InventoryEntry
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getSku()->getCode();
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
