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
 * @nae \Rbs\Price\Tax\TaxInterface
 */
interface TaxInterface
{
	const ROUNDING_TOTAL = 't';
	const ROUNDING_ROW = 'r';
	const ROUNDING_UNIT = 'u';

	/**
	 * @return string
	 */
	public function getCode();

	/**
	 * @param string $category
	 * @param string $zone
	 * @return float
	 */
	public function getRate($category, $zone);

	/**
	 * @return boolean
	 */
	public function getCascading();

	/**
	 * Return t => total, l => row, u => unit
	 * @return string
	 */
	public function getRounding();

	/**
	 * @return array
	 */
	public function toArray();
}