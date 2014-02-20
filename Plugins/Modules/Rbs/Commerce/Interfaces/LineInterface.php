<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\LineInterface
*/
interface LineInterface
{
	/**
	 * @return integer
	 */
	public function getIndex();

	/**
	 * @return string
	 */
	public function getKey();

	/**
	 * @return integer
	 */
	public function getQuantity();

	/**
	 * @return string
	 */
	public function getDesignation();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @return \Rbs\Commerce\Interfaces\LineItemInterface[]
	 */
	public function getItems();

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTaxes();

	/**
	 * @return float|null
	 */
	public function getAmount();

	/**
	 * @return float|null
	 */
	public function getAmountWithTaxes();

	/**
	 * @return array
	 */
	public function toArray();
}