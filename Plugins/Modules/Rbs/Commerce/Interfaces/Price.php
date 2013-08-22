<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\Price
*/
interface Price
{
	/**
	 * @return float
	 */
	public function getValue();

	/**
	 * @return array<taxCode => category>
	 */
	public function getTaxCategories();
}