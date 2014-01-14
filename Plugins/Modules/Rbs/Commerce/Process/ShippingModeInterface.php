<?php
namespace Rbs\Commerce\Process;

/**
* @name \Rbs\Commerce\Process\ShippingModeInterface
*/
interface ShippingModeInterface
{
	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return integer[]
	 */
	public function getLineKeys();

	/**
	 * @return \Rbs\Geo\Address\AddressInterface
	 */
	public function getAddress();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @return array
	 */
	public function toArray();
}