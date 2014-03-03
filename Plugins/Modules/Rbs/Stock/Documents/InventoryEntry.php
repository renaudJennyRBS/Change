<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
