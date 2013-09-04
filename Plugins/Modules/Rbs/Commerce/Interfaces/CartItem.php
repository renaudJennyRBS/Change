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
	 * @return integer|null
	 */
	public function getReservationQuantity();

	/**
	 * @return float|null
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