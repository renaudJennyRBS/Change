<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Price\Tax;

/**
 * @name \Rbs\Price\Tax\BillingAreaInterface
 */
interface BillingAreaInterface
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getCurrencyCode();

	/**
	 * @return \Rbs\Price\Tax\TaxInterface []
	 */
	public function getTaxes();
}