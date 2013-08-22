<?php
namespace ChangeTests\Modules\Commerce\Interfaces;

use Rbs\Commerce\Interfaces\CartLine;

/**
* @name \ChangeTests\Modules\Commerce\Interfaces\CartLineTest
*/
class CartLineTest extends \PHPUnit_Framework_TestCase
{
	public function testConstructor()
	{
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\CartLine', new Fake_CartLine_1d21fgd());
	}
}

class Fake_CartLine_1d21fgd implements CartLine
{
	/**
	 * @return integer
	 */
	public function getNumber()
	{
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
	}

	/**
	 * @return float
	 */
	public function getQuantity()
	{
	}

	/**
	 * @return string
	 */
	public function getDesignation()
	{
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\CartItem[]
	 */
	public function getItems()
	{
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
	}

	public function serialize()
	{
	}

	public function unserialize($serialized)
	{
	}
}