<?php
namespace Rbs\Commerce\Interfaces;

use Rbs\Commerce\CommerceServices;

/**
* @name \Rbs\Commerce\Interfaces\CartLineConfigCapable
*/
interface CartLineConfigCapable
{
	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param array $parameters
	 * @return \Rbs\Commerce\Interfaces\CartLineConfig
	 */
	public function getCartLineConfig(CommerceServices $commerceServices, array $parameters);
}