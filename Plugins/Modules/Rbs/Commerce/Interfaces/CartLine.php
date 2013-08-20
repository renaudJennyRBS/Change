<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\CartLine
*/
interface CartLine extends \Serializable
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
	public function getNumber();

	/**
	 * @return string
	 */
	public function getKey();

	/**
	 * @return float
	 */
	public function getQuantity();

	/**
	 * @return string
	 */
	public function getDesignation();

	/**
	 * @return \Rbs\Commerce\Interfaces\CartItem[]
	 */
	public function getItems();

	/**
	 * @return array[]
	 */
	public function getOptions();
}