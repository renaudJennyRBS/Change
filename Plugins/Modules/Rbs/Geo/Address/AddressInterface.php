<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Address;

/**
 * @name \Rbs\Geo\Address\AddressInterface
 */
interface AddressInterface
{
	const COUNTRY_CODE_FIELD_NAME = 'countryCode';
	const ZIP_CODE_FIELD_NAME = 'zipCode';
	const LOCALITY_FIELD_NAME = 'locality';

	/**
	 * @return string
	 */
	public function getCountryCode();

	/**
	 * @return string
	 */
	public function getZipCode();

	/**
	 * @return string
	 */
	public function getLocality();

	/**
	 * @return array|null
	 */
	public function getFields();

	/**
	 * @return array
	 */
	public function getLines();

	/**
	 * @return array
	 */
	public function toArray();
}