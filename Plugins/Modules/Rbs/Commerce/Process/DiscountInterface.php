<?php
namespace Rbs\Commerce\Process;

/**
* @name \Rbs\Commerce\Process\DiscountInterface
*/
interface DiscountInterface
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
	 * @return float|null
	 */
	public function getAmount();

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTaxes();

	/**
	 * @return float|null
	 */
	public function getAmountWithTaxes();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @return array
	 */
	public function toArray();
}