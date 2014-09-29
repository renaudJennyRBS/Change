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
* @name \Rbs\Commerce\Interfaces\LineInterface
*/
interface LineInterface
{
	/**
	 * @return integer
	 */
	public function getIndex();

	/**
	 * @return string
	 */
	public function getKey();

	/**
	 * @return integer
	 */
	public function getQuantity();

	/**
	 * @return string
	 */
	public function getDesignation();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @return \Rbs\Commerce\Interfaces\LineItemInterface[]
	 */
	public function getItems();

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTaxes();

	/**
	 * @return float|null
	 */
	public function getAmountWithoutTaxes();

	/**
	 * @return float|null
	 */
	public function getAmountWithTaxes();

	/**
	 * @return float|null
	 */
	public function getBasedAmountWithoutTaxes();

	/**
	 * @return float|null
	 */
	public function getBasedAmountWithTaxes();

	/**
	 * @return array
	 */
	public function toArray();


	/**
	 * @deprecated
	 * @see \Rbs\Commerce\Interfaces\LineInterface::getAmountWithoutTaxes()
	 */
	public function getAmount();

	/**
	 * @deprecated
	 * @see \Rbs\Commerce\Interfaces\LineInterface::getBasedAmountWithoutTaxes()
	 */
	public function getBasedAmount();
}