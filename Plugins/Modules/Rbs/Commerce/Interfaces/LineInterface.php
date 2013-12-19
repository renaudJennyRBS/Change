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
	 * @return array
	 */
	public function toArray();
}