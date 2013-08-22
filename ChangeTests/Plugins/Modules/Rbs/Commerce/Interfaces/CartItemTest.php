<?php
namespace ChangeTests\Modules\Commerce\Interfaces;

use Rbs\Commerce\Interfaces\CartItem;

/**
 * @name \ChangeTests\Modules\Commerce\Interfaces\CartItemTest
 */
class CartItemTest extends \PHPUnit_Framework_TestCase
{
	public function testConstructor()
	{
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\CartItem', new Fake_CartItem_421241());
	}
}

class Fake_CartItem_421241 implements CartItem
{
	/**
	 * @return string
	 */
	public function getCodeSKU()
	{
	}

	/**
	 * @return float
	 */
	public function getReservationQuantity()
	{
	}

	/**
	 * @return float
	 */
	public function getPriceValue()
	{
	}

	/**
	 * @return CartTax[]
	 */
	public function getCartTaxes()
	{
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
	}

	/**
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
	}

	/**
	 * @param string $serialized <p>
	 * @return void
	 */
	public function unserialize($serialized)
	{
	}
}