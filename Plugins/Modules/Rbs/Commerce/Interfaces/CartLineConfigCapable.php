<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\CartLineConfigCapable
*/
interface CartLineConfigCapable
{
	/**
	 * @return \Rbs\Commerce\Interfaces\CartLineConfig
	 */
	public function getCartLineConfig();
}