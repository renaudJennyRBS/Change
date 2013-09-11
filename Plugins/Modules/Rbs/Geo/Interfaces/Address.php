<?php
namespace Rbs\Geo\Interfaces;

/**
* @name \Rbs\Geo\Interfaces\Address
*/
interface Address
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
}