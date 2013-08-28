<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\CartLine
*/
interface CartLine extends \Serializable
{
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
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions();

	/**
	 * @param $codeSKU
	 * @return \Rbs\Commerce\Interfaces\CartItem|null
	 */
	public function getItemByCodeSKU($codeSKU);

	/**
	 * @param \Rbs\Commerce\Interfaces\CartItem $item
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Interfaces\CartItem
	 */
	public function appendItem($item);

	/**
	 * @param string $codeSKU
	 * @return \Rbs\Commerce\Interfaces\CartItem|null
	 */
	public function removeItemByCodeSKU($codeSKU);

	/**
	 * @return float|null
	 */
	public function getUnitPriceValue();

	/**
	 * @return float|null
	 */
	public function getPriceValue();

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax();

	/**
	 * @return array
	 */
	public function toArray();
}