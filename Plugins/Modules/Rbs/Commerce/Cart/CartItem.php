<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\CartItem as CartItemInterfaces;

/**
 * @name \Rbs\Commerce\Cart\CartItem
 */
class CartItem implements CartItemInterfaces
{
	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var float|null
	 */
	protected $reservationQuantity;

	/**
	 * @var float|null
	 */
	protected $priceValue;

	/**
	 * @var CartTax[]
	 */
	protected $cartTaxes = array();

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @param string $codeSKU
	 */
	function __construct($codeSKU)
	{
		$this->codeSKU = $codeSKU;
	}

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
	 * @return string
	 */
	public function getCodeSKU()
	{
		return $this->codeSKU;
	}

	/**
	 * @param float|null $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity)
	{
		$this->reservationQuantity = ($reservationQuantity === null) ? $reservationQuantity : floatval($reservationQuantity);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getReservationQuantity()
	{
		return $this->reservationQuantity;
	}

	/**
	 * @param float|null $priceValue
	 * @return $this
	 */
	public function setPriceValue($priceValue)
	{
		$this->priceValue = ($priceValue === null) ? $priceValue : floatval($priceValue);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		return $this->priceValue;
	}

	/**
	 * @param CartTax[] $cartTaxes
	 * @return $this
	 */
	public function setCartTaxes($cartTaxes)
	{
		$this->cartTaxes = array();
		if (is_array($cartTaxes))
		{
			foreach ($cartTaxes as $cartTax)
			{
				$this->appendCartTaxes($cartTax);
			}
		}
		return $this;
	}

	/**
	 * @return CartTax[]
	 */
	public function getCartTaxes()
	{
		return $this->cartTaxes;
	}

	/**
	 * @param CartTax $cartTax
	 */
	public function appendCartTaxes(CartTax $cartTax)
	{
		$this->cartTaxes[] = $cartTax;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
		if ($this->options === null)
		{
			$this->options = new \Zend\Stdlib\Parameters();
		}
		return $this->options;
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		$serializedData = array(
			'codeSKU' => $this->codeSKU,
			'reservationQuantity' => $this->reservationQuantity,
			'priceValue' => $this->priceValue,
			'cartTaxes' => $this->cartTaxes,
			'options' => $this->options);
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
		$this->codeSKU = $serializedData['codeSKU'];
		$this->reservationQuantity = $serializedData['reservationQuantity'];
		$this->priceValue = $serializedData['priceValue'];
		$this->cartTaxes = $serializedData['cartTaxes'];
		$this->options = $serializedData['options'];

		foreach ($this->cartTaxes as $cartTax)
		{
			/* @var $cartTax CartTax */
			$cartTax->setCart($cart);
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'codeSKU' => $this->codeSKU,
			'reservationQuantity' => $this->reservationQuantity,
			'priceValue' => $this->priceValue,
			'cartTaxes' => array(),
			'options' => $this->getOptions()->toArray());

		foreach ($this->cartTaxes as $cartTax)
		{
			$array['cartTaxes'][] = $cartTax->toArray();
		}
		return $array;
	}
}