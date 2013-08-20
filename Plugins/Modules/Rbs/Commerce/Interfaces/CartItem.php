<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\CartItem
*/
interface CartItem extends \Serializable
{
	/**
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @return $this
	 */
	public function setCart($cart);

	/**
	 * @return \Rbs\Commerce\Interfaces\Cart
	 */
	public function getCart();

	/**
	 * @return integer
	 */
	public function getLineNumber();

	/**
	 * @return string
	 */
	public function getSKUCode();

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
	 * @return array[]
	 */
	public function getOptions();
}