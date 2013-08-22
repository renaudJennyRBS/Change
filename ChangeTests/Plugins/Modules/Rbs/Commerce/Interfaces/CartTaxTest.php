<?php
namespace ChangeTests\Modules\Commerce\Interfaces;

use Rbs\Commerce\Interfaces\CartTax;
use Rbs\Commerce\Interfaces\Tax;

/**
* @name \ChangeTests\Modules\Commerce\Interfaces\CartTaxTest
*/
class CartTaxTest extends \PHPUnit_Framework_TestCase
{
	public function testConstructor()
	{
		$this->assertInstanceOf('\Rbs\Commerce\Interfaces\CartTax', new Fake_CartTax_521152());
	}
}

class Fake_CartTax_521152 implements CartTax
{

	/**
	 * @return Tax
	 */
	public function getTax()
	{
	}

	/**
	 * @return string
	 */
	public function getCategory()
	{
	}

	/**
	 * @return string
	 */
	public function getZone()
	{
	}

	/**
	 * @return float
	 */
	public function getRate()
	{
	}

	/**
	 * @return float
	 */
	public function getValue()
	{
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\TaxApplication $taxApplication
	 * @return $this
	 */
	public function fromTaxApplication($taxApplication)
	{
	}

	/**
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
	}

	/**
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
	}
}