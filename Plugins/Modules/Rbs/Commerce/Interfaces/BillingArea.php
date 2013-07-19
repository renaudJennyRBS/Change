<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\BillingArea
*/
interface BillingArea
{
	/**
	 * @return string
	 */
	public function getCurrencyCode();

	/**
	 * @return Tax[]
	 */
	public function getTaxes();
}