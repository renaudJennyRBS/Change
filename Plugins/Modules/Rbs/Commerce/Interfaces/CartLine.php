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
}