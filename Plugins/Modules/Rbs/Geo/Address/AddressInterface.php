<?php
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
	public function toArray();
}