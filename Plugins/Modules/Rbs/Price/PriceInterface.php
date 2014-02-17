<?php
namespace Rbs\Price;

/**
* @name \Rbs\Price\PriceInterface
*/
interface PriceInterface
{
	/**
	 * @return float
	 */
	public function getValue();

	/**
	 * @return boolean
	 */
	public function isWithTax();

	/**
	 * @return boolean
	 */
	public function isDiscount();

	/**
	 * @return float|null
	 */
	public function getBasePriceValue();

	/**
	 * @return array<taxCode => category>
	 */
	public function getTaxCategories();
}