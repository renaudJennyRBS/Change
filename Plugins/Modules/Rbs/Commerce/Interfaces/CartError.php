<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\CartError
*/
interface CartError
{
	/**
	 * @return string
	 */
	public function getMessage();

	/**
	 * @return string
	 */
	public function getLineKey();
}