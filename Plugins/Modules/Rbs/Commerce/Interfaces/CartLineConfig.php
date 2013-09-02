<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\CartLineConfig
*/
interface CartLineConfig
{
	/**
	 * @return string
	 */
	public function getKey();

	/**
	 * @return string
	 */
	public function getDesignation();

	/**
	 * @return \Rbs\Commerce\Interfaces\CartItemConfig[]
	 */
	public function getItemConfigArray();

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
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return $this
	 */
	public function evaluatePrice($commerceServices);

	/**
	 * @return float|null
	 */
	public function getPriceValue();

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax();
}