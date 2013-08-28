<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\CartTax as CartTaxInterfaces;
use Rbs\Commerce\Interfaces\Tax;

/**
* @name \Rbs\Commerce\Cart\CartTax
*/
class CartTax implements CartTaxInterfaces
{
	/**
	 * @var Tax
	 */
	protected $tax;

	/**
	 * @var string
	 */
	protected $category;

	/**
	 * @var string
	 */
	protected $zone;

	/**
	 * @var float
	 */
	protected $rate;

	/**
	 * @var float
	 */
	protected $value;

	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @param Cart $cart
	 * @return $this
	 */
	public function setCart(Cart $cart)
	{
		if ($this->serializedData)
		{
			$this->restoreSerializedData($cart);
		}
		return $this;
	}

	/**
	 * @return Tax
	 */
	public function getTax()
	{
		return $this->tax;
	}

	/**
	 * @return string
	 */
	public function getCategory()
	{
		return $this->category;
	}

	/**
	 * @return string
	 */
	public function getZone()
	{
		return $this->zone;
	}

	/**
	 * @return float
	 */
	public function getRate()
	{
		return $this->rate;
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * @param float $add
	 * @return $this
	 */
	public function addValue($add)
	{
		$this->value += $add;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\TaxApplication $taxApplication
	 * @return $this
	 */
	public function fromTaxApplication($taxApplication)
	{
		$this->tax = $taxApplication->getTax();
		$this->category = $taxApplication->getCategory();
		$this->zone = $taxApplication->getZone();
		$this->rate = $taxApplication->getRate();
		$this->value = $taxApplication->getValue();
		return $this;
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		$serializedData = array(
			'tax' => $this->tax,
			'category' => $this->category,
			'zone' => $this->zone,
			'rate' => $this->rate,
			'value' => $this->value);
		return serialize((new CartStorage())->getSerializableValue($serializedData));
	}

	/**
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->serializedData = unserialize($serialized);
	}

	protected function restoreSerializedData(Cart $cart)
	{
		$serializedData = (new CartStorage())->restoreSerializableValue($this->serializedData, $cart->getCommerceServices());
		$this->serializedData = null;
		$this->tax = $serializedData['tax'];
		$this->category = $serializedData['category'];
		$this->zone = $serializedData['zone'];
		$this->rate = $serializedData['rate'];
		$this->value = $serializedData['value'];
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'tax' => $this->tax ? $this->tax->getCode() : null,
			'category' => $this->category,
			'zone' => $this->zone,
			'rate' => $this->rate,
			'value' => $this->value);
		return $array;
	}
}