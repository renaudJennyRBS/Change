<?php
namespace Rbs\Geo\Address;

/**
 * @name \Rbs\Geo\BaseAddress\AddressInterface
 */
interface AddressInterface
{
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
	 * @return string[]
	 */
	public function getLines();

	/**
	 * @return array|null
	 */
	public function getFields();
}