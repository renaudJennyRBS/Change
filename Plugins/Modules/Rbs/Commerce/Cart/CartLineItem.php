<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\LineItemInterface;

/**
 * @name \Rbs\Commerce\Cart\CartLineItem
 */
class CartLineItem implements LineItemInterface, \Serializable
{
	/**
	 * @var string
	 */
	protected $codeSKU;

	/**
	 * @var integer|null
	 */
	protected $reservationQuantity;

	/**
	 * @var \Rbs\Commerce\Cart\CartPrice
	 */
	protected $price;

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
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
	 * @param string|array $codeSKU
	 */
	function __construct($codeSKU)
	{
		if (is_array($codeSKU))
		{
			$this->fromArray($codeSKU);
		}
		else
		{
			$this->codeSKU = $codeSKU;
		}
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		if ($this->serializedData)
		{
			$this->restoreSerializedData($documentManager);
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
	 * @param integer|null $reservationQuantity
	 * @return $this
	 */
	public function setReservationQuantity($reservationQuantity)
	{
		$this->reservationQuantity = ($reservationQuantity === null) ? $reservationQuantity : intval($reservationQuantity);
		return $this;
	}

	/**
	 * @return integer|null
	 */
	public function getReservationQuantity()
	{
		return $this->reservationQuantity;
	}

	/**
	 * @param \Rbs\Price\PriceInterface|array|float|null $price
	 * @return $this
	 */
	public function setPrice($price)
	{
		$this->price = new \Rbs\Commerce\Cart\CartPrice($price);
		return $this;
	}

	/**
	 * @return \Rbs\Price\PriceInterface|null
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * @param float|null $priceValue
	 * @return $this
	 */
	public function setPriceValue($priceValue)
	{
		if ($this->price === null)
		{
			$this->setPrice($priceValue);
		}
		$this->price->setValue($priceValue);
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		return $this->price ? $this->price->getValue() : null;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $cartTaxes
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
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getCartTaxes()
	{
		return $this->cartTaxes;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication $cartTax
	 */
	public function appendCartTaxes(\Rbs\Price\Tax\TaxApplication $cartTax)
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
			'price' => $this->price,
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

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	protected function restoreSerializedData(\Change\Documents\DocumentManager $documentManager)
	{
		$serializedData = (new CartStorage())->setDocumentManager($documentManager)->restoreSerializableValue($this->serializedData);
		$this->serializedData = null;
		$this->codeSKU = $serializedData['codeSKU'];
		$this->reservationQuantity = $serializedData['reservationQuantity'];
		$this->price = $serializedData['price'];
		$this->cartTaxes = $serializedData['cartTaxes'];
		$this->options = $serializedData['options'];
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'codeSKU':
					$this->codeSKU = strval($value);
					break;
				case 'reservationQuantity':
					$this->reservationQuantity = intval($value);
					break;
				case 'priceValue':
					$this->setPriceValue($value);
					break;
				case 'price':
					$this->price = new CartPrice($value);
					break;
				case 'options':
					if (is_array($value))
					{
						foreach ($value as $optName => $optValue)
						{
							$this->getOptions()->set($optName, $optValue);
						}
					}
					break;
				case 'cartTaxes':
					if (is_array($value))
					{
						foreach ($value as $cartTax)
						{
							if (is_array($cartTax) && isset($cartTax['taxCode']) && isset($cartTax['category'])  && isset($cartTax['zone']))
							{
								$taxApplication = new \Rbs\Price\Tax\TaxApplication($cartTax['taxCode'], $cartTax['category'], $cartTax['zone']);
								if (isset($cartTax['rate']))
								{
									$taxApplication->setRate($cartTax['rate']);
								}
								if (isset($cartTax['value']))
								{
									$taxApplication->setValue($cartTax['value']);
								}
								$this->appendCartTaxes($taxApplication);
							}

						}
					}
					break;
			}

			if ($this->reservationQuantity === null && $this->codeSKU)
			{
				$this->reservationQuantity = 1;
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'codeSKU' => $this->codeSKU,
			'reservationQuantity' => $this->reservationQuantity,
			'priceValue' => $this->getPriceValue(),
			'cartTaxes' => array(),
			'options' => $this->getOptions()->toArray());
		foreach ($this->cartTaxes as $cartTax)
		{
			$array['cartTaxes'][] = $cartTax->toArray();
		}
		return $array;
	}
}