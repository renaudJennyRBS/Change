<?php
namespace Rbs\Commerce\Interfaces;

use Rbs\Commerce\Services\CommerceServices;
use Zend\EventManager\Event;

/**
* @name \Rbs\Commerce\Interfaces\CartLineConfigCapable
*/
interface CartLineConfigCapable
{
	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param array $parameters
	 * @return \Rbs\Commerce\Interfaces\CartLineConfig
	 */
	public function getCartLineConfig(CommerceServices $commerceServices, array $parameters);
}