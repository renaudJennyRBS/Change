<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Price;

/**
* @name \Rbs\Price\PriceInterface
*/
interface PriceInterface
{
	/**
	 * @return float
	 */
	public function getValue();

	/**
	 * @return boolean
	 */
	public function isWithTax();

	/**
	 * @return boolean
	 */
	public function isDiscount();

	/**
	 * @return float|null
	 */
	public function getBasePriceValue();

	/**
	 * @return array<taxCode => category>
	 */
	public function getTaxCategories();
}