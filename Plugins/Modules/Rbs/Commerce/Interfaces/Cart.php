<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\Cart
*/
interface Cart extends \Serializable
{
	/**
	 * @return \Rbs\Commerce\Services\CommerceServices
	 */
	public function getCommerceServices();

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices($commerceServices);

	/**
	 * @return string
	 */
	public function getIdentifier();

	/**
	 * @return integer|null
	 */
	public function getOwnerId();

	/**
	 * @return boolean
	 */
	public function isLocked();

	/**
	 * @param \DateTime|null $lastUpdate
	 * @return \DateTime
	 */
	public function lastUpdate(\DateTime $lastUpdate = null);

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext();

	/**
	 * @return \Rbs\Commerce\Interfaces\CartLine[]
	 */
	public function getLines();

	/**
	 * @return \Rbs\Commerce\Interfaces\CartItem[]
	 */
	public function getItems();

	/**
	 * @return \Rbs\Commerce\Interfaces\BillingArea|null
	 */
	public function getBillingArea();

	/**
	 * @return string|null
	 */
	public function getZone();
}