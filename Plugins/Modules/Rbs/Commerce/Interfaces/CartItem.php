<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\CartItem
*/
interface CartItem extends \Serializable
{
	/**
	 * @return string
	 */
	public function getCodeSKU();

	/**
	 * @return float
	 */
	public function getReservationQuantity();

	/**
	 * @return float
	 */
	public function getPriceValue();

	/**
	 * @return CartTax[]
	 */
	public function getCartTaxes();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @return array
	 */
	public function toArray();
}