<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\LineItemInterface
*/
interface LineItemInterface
{
	/**
	 * @return string
	 */
	public function getCodeSKU();

	/**
	 * @return integer|null
	 */
	public function getReservationQuantity();

	/**
	 * @return \Rbs\Price\PriceInterface|null
	 */
	public function getPrice();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @return array
	 */
	public function toArray();
}