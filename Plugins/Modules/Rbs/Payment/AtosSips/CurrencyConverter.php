<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\AtosSips;

/**
* @name \Rbs\Payment\AtosSips\CurrencyConverter
*/
class CurrencyConverter
{
	/**
	 * @param float $amount
	 * @param string $currencyCode
	 * @throws \RuntimeException
	 * @return array
	 */
	public function toParams($amount, $currencyCode) {
		switch ($currencyCode) {
			case 'EUR' : //Euro
				return [strval(intval($amount * 100)), '978'];
			case 'USD' : //US Dollar
				return [strval(intval($amount * 100)), '840'];
			case 'CHF' : //Swiss Franc
				return [strval(intval($amount * 100)), '756'];
			case 'GBP' : //Pound Sterling
				return [strval(intval($amount * 100)), '826'];
			case 'CAD' : //Canadian Dollar
				return [strval(intval($amount * 100)), '124'];
		}
		throw new \RuntimeException('Invalid currencyCode:' . $currencyCode);
	}

	/**
	 * @param string $amount
	 * @param string $currencyCode
	 * @throws \RuntimeException
	 * @return array
	 */
	public function fromParams($amount, $currencyCode) {
		switch ($currencyCode) {
			case '978' : //Euro
				return [floatval($amount) / 100.0, 'EUR'];
			case '840' : //US Dollar
				return [floatval($amount) / 100.0, 'USD'];
			case '756' : //Swiss Franc
				return [floatval($amount) / 100.0, 'CHF'];
			case '826' : //Pound Sterling
				return [floatval($amount) / 100.0, 'GBP'];
			case '124' : //Canadian Dollar
				return [floatval($amount) / 100.0, 'CAD'];
		}
		throw new \RuntimeException('Invalid currencyCode:' . $currencyCode);
	}
} 