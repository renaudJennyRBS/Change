<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\TaxApplication
*/
interface TaxApplication
{

	/**
	 * @return \Rbs\Commerce\Interfaces\Tax
	 */
	public function getTax();

	/**
	 * @return string
	 */
	public function getZone();

	/**
	 * @return string
	 */
	public function getCategory();

	/**
	 * @return float
	 */
	public function getRate();

	/**
	 * @return float
	 */
	public function getValue();

	/**
	 * @param \Rbs\Commerce\Interfaces\CartTax $cartTax
	 * @return $this
	 */
	public function fromCartTax($cartTax);
}