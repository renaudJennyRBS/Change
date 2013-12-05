<?php
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