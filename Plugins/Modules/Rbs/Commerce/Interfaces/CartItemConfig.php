<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\CartItemConfig
*/
interface CartItemConfig
{
	/**
	 * @return string
	 */
	public function getCodeSKU();

	/**
	 * @param float $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity);

	/**
	 * @return float
	 */
	public function getReservationQuantity();

	/**
	 * @param float $priceValue
	 * @return $this
	 */
	public function setPriceValue($priceValue);

	/**
	 * @return float
	 */
	public function getPriceValue();

	/**
	 * @param \Rbs\Commerce\Interfaces\TaxApplication $taxApplication
	 * @return $this
	 */
	public function addTaxApplication(\Rbs\Commerce\Interfaces\TaxApplication $taxApplication = null);

	/**
	 * @return \Rbs\Commerce\Interfaces\TaxApplication|\Rbs\Commerce\Interfaces\TaxApplication[]
	 */
	public function getTaxApplication();

	/**
	 * @return array
	 */
	public function getOptions();

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setOption($name, $value);

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getOption($name, $defaultValue = null);
}