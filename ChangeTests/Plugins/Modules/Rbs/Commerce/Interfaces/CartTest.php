<?php
namespace ChangeTests\Modules\Commerce\Interfaces;

use Rbs\Commerce\Interfaces\Cart;

class CartTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstructor()
	{
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\Cart', new Fake_Cart_712562314());
	}
}

class Fake_Cart_712562314 implements Cart
{

	/**
	 * @return \Rbs\Commerce\Services\CommerceServices
	 */
	public function getCommerceServices()
	{
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices($commerceServices)
	{
	}

	/**
	 * @return string
	 */
	public function getIdentifier()
	{
	}

	/**
	 * @return integer|null
	 */
	public function getOwnerId()
	{
	}

	/**
	 * @return boolean
	 */
	public function isLocked()
	{
	}

	/**
	 * @param \DateTime|null $lastUpdate
	 * @return \DateTime
	 */
	public function lastUpdate(\DateTime $lastUpdate = null)
	{
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext()
	{
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\CartLine[]
	 */
	public function getLines()
	{
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\CartItem[]
	 */
	public function getItems()
	{
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\BillingArea|null
	 */
	public function getBillingArea()
	{
	}

	/**
	 * @return string|null
	 */
	public function getZone()
	{
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
	}

	/**
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
	}
}