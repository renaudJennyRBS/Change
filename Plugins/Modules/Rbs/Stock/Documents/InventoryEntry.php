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
		$sku = $this->getSku();
		if ($sku)
		{
			return $sku->getCode();
		}
		return null;
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
