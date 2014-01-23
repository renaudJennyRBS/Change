<?php
namespace Rbs\Commerce\Cart;

/**
 * @name \Rbs\Commerce\Cart\Cart
 */
class Cart implements \Serializable
{
	/**
	 * @var \Rbs\Commerce\Cart\CartManager
	 */
	protected $cartManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var \Rbs\Price\Tax\BillingAreaInterface|null
	 */
	protected $billingArea;

	/**
	 * @var string|null
	 */
	protected $zone;

	/**
	 * @var integer
	 */
	protected $webStoreId = 0;

	/**
	 * @var integer
	 */
	protected $userId = 0;

	/**
	 * @var integer
	 */
	protected $ownerId = 0;

	/**
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * @var integer
	 */
	protected $transactionId = 0;

	/**
	 * @var boolean
	 */
	protected $ordered = false;

	/**
	 * @var \DateTime
	 */
	protected $lastUpdate;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

	/**
	 * @var CartError[]
	 */
	protected $errors = array();

	/**
	 * @var \Rbs\Commerce\Cart\CartLine[]
	 */
	protected $lines = array();

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $linesTaxesValues = array();

	/**
	 * @var string
	 */
	protected $email;

	/**
	 * @var \Rbs\Geo\Address\AddressInterface
	 */
	protected $address;

	/**
	 * @var \Rbs\Commerce\Process\ShippingModeInterface[]
	 */
	protected $shippingModes = array();

	/**
	 * @var \Rbs\Commerce\Process\CouponInterface[]
	 */
	protected $coupons = array();

	/**
	 * @var \Rbs\Commerce\Cart\CartLine[]
	 */
	protected $fees = array();

	/**
	 * @var \Rbs\Commerce\Cart\CartLine[]
	 */
	protected $discounts = array();

	/**
	 * @var \Rbs\Price\Tax\TaxApplication[]
	 */
	protected $taxesValues = array();

	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @param string $identifier
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 */
	function __construct($identifier, \Rbs\Commerce\Cart\CartManager $cartManager = null)
	{
		$this->identifier = $identifier;
		$this->cartManager = $cartManager;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager = null)
	{
		$this->documentManager = $documentManager;
		if ($documentManager && $this->serializedData)
		{
			$this->restoreSerializedData();
		}
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartManager
	 */
	public function getCartManager()
	{
		return $this->cartManager;
	}

	/**
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 * @return $this
	 */
	public function setCartManager($cartManager)
	{
		$this->cartManager = $cartManager;

		return $this;
	}

	/**
	 * @param string $identifier
	 * @return $this
	 */
	public function setIdentifier($identifier)
	{
		$this->identifier = $identifier;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}

	/**
	 * @param integer $webStoreId
	 * @return $this
	 */
	public function setWebStoreId($webStoreId)
	{
		$this->webStoreId = intval($webStoreId);
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getWebStoreId()
	{
		return $this->webStoreId;
	}

	/**
	 * @return boolean|null
	 */
	public function getPricesValueWithTax()
	{
		return $this->getContext()->get('pricesValueWithTax', null);
	}

	/**
	 * @param boolean $pricesValueWithTax
	 * @return $this|\Zend\Stdlib\Parameters
	 */
	public function setPricesValueWithTax($pricesValueWithTax)
	{
		$this->getContext()->set('pricesValueWithTax', $pricesValueWithTax);
		return $this;
	}


	/**
	 * @return \Rbs\Store\Documents\Webstore|null
	 */
	public function getWebStore()
	{
		return $this->getDocumentManager()->getDocumentInstance($this->getWebStoreId(), 'Rbs_Store_Webstore');
	}

	/**
	 * @param \Rbs\Price\Tax\BillingAreaInterface|null $billingArea
	 * @return $this
	 */
	public function setBillingArea($billingArea)
	{
		$this->billingArea = $billingArea;
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\BillingAreaInterface|null
	 */
	public function getBillingArea()
	{
		return $this->billingArea;
	}

	/**
	 * @return string|null
	 */
	public function getCurrencyCode()
	{
		return $this->billingArea ? $this->billingArea->getCurrencyCode() : null;
	}

	/**
	 * @param string|null $zone
	 * @return $this
	 */
	public function setZone($zone)
	{
		$this->zone = $zone;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getZone()
	{
		return $this->zone;
	}

	/**
	 * @param integer $userId
	 * @return $this
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param integer $ownerId
	 * @return $this
	 */
	public function setOwnerId($ownerId)
	{
		$this->ownerId = intval($ownerId);
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getOwnerId()
	{
		return $this->ownerId;
	}

	/**
	 * @param string $email
	 * @return $this
	 */
	public function setEmail($email)
	{
		$this->email = $email;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param \Rbs\Geo\Address\AddressInterface $address
	 * @return $this
	 */
	public function setAddress($address)
	{
		$this->address = $address;
		return $this;
	}

	/**
	 * @return \Rbs\Geo\Address\AddressInterface
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @param boolean $locked
	 * @return $this
	 */
	public function setLocked($locked)
	{
		$this->locked = ($locked == true);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isLocked()
	{
		return $this->locked;
	}

	/**
	 * @param integer $transactionId
	 * @return $this
	 */
	public function setTransactionId($transactionId)
	{
		$this->transactionId = $transactionId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getTransactionId()
	{
		return $this->transactionId;
	}

	/**
	 * @param boolean $ordered
	 * @return $this
	 */
	public function setOrdered($ordered)
	{
		$this->ordered = $ordered;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getOrdered()
	{
		return $this->ordered;
	}

	/**
	 * @return boolean
	 */
	public function isEmpty()
	{
		return count($this->lines) === 0;
	}

	/**
	 * @param \Rbs\Commerce\Cart\CartError[] $errors
	 * @return $this
	 */
	public function setErrors(array $errors)
	{
		$this->errors = array();
		foreach ($errors as $error)
		{
			$this->addError($error);
		}

		return $this;
	}

	/**
	 * @return CartError[]
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * @param CartError $error
	 * @return $this
	 */
	public function addError($error)
	{
		if ($error instanceof CartError)
		{
			$this->errors[] = $error;
		}
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function hasError()
	{
		return count($this->errors) > 0;
	}

	/**
	 * @param \DateTime|null $lastUpdate
	 * @return \DateTime
	 */
	public function lastUpdate(\DateTime $lastUpdate = null)
	{
		if ($lastUpdate !== null)
		{
			$this->lastUpdate = $lastUpdate;
		}
		return $this->lastUpdate;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext()
	{
		if ($this->context === null)
		{
			$this->context = new \Zend\Stdlib\Parameters();
		}
		return $this->context;
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartLine[]
	 */
	public function getLines()
	{
		return $this->lines;
	}

	/**
	 * @param integer $lineNumber
	 * @return \Rbs\Commerce\Cart\CartLine|null
	 */
	public function getLineByNumber($lineNumber)
	{
		$idx = $lineNumber - 1;
		return (isset($this->lines[$idx])) ? $this->lines[$idx] : null;
	}

	/**
	 * @param string $lineKey
	 * @return \Rbs\Commerce\Cart\CartLine|null
	 */
	public function getLineByKey($lineKey)
	{
		foreach ($this->lines as $line)
		{
			if ($line->getKey() === $lineKey)
			{
				return $line;
			}
		}
		return null;
	}

	/**
	 * @param array $parameters
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function getNewLine($parameters)
	{
		return new CartLine($parameters);
	}

	/**
	 * @param CartLine $line
	 * @param integer $lineIndex
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function insertLineAt(CartLine $line, $lineIndex = 0)
	{
		if ($line instanceof CartLine)
		{
			$countLines = count($this->lines);
			if ($lineIndex < 0 || $lineIndex >= $countLines)
			{
				return $this->appendLine($line);
			}
			if ($this->getLineByKey($line->getKey()))
			{
				throw new \RuntimeException('Duplicate line key: ' . $line->getKey(), 999999);
			}
			array_splice($this->lines, $lineIndex, 0, array($line));
			foreach ($this->lines as $idx => $line)
			{
				$line->setIndex($idx);
			}
			return $line;
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 should be a CartLine', 999999);
		}
	}

	/**
	 * @param CartLine $line
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function appendLine(CartLine $line)
	{
		if ($this->getLineByKey($line->getKey()))
		{
			throw new \RuntimeException('Duplicate line key: ' . $line->getKey(), 999999);
		}
		$index = count($this->lines);
		$this->lines[] = $line;
		$line->setIndex($index);
		return $line;
	}

	/**
	 * @param integer $index
	 * @return \Rbs\Commerce\Cart\CartLine|null
	 */
	public function removeLineAt($index)
	{
		if (!isset($this->lines[$index]))
		{
			return null;
		}
		$removedLine = $this->lines[$index];
		array_splice($this->lines, $index, 1);
		foreach ($this->lines as $idx => $line)
		{
			$line->setIndex($idx);
		}
		return $removedLine;
	}

	/**
	 * @param string $lineKey
	 * @return \Rbs\Commerce\Cart\CartLine|null
	 */
	public function removeLineByKey($lineKey)
	{
		$line = $this->getLineByKey($lineKey);
		if ($line)
		{
			return $this->removeLineAt($line->getIndex());
		}
		return $line;
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartLine[]
	 */
	public function removeAllLines()
	{
		$removed = $this->lines;
		$this->lines = array();
		return $removed;
	}

	/**
	 * @param string $lineKey
	 * @param integer $newQuantity
	 * @return CartLine|null
	 */
	public function updateLineQuantity($lineKey, $newQuantity)
	{
		$line = $this->getLineByKey($lineKey);
		if ($line)
		{
			return $line->setQuantity(intval($newQuantity));
		}
		return $line;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxInterface[]
	 */
	public function getTaxes()
	{
		$taxes = $this->billingArea ?  $this->billingArea->getTaxes() : [];
		if ($taxes instanceof \Change\Documents\DocumentArrayProperty)
		{
			$taxes = $taxes->toArray();
		}
		return $taxes ;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getLinesTaxesValues()
	{
		return $this->linesTaxesValues;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $linesTaxesValues
	 * @return $this
	 */
	public function setLinesTaxesValues(array $linesTaxesValues)
	{
		$this->linesTaxesValues = $linesTaxesValues;
		return $this;
	}

	/**
	 * @param \Rbs\Price\Tax\TaxApplication[] $taxesValues
	 * @return $this
	 */
	public function setTaxesValues(array $taxesValues)
	{
		$this->taxesValues = $taxesValues;
		return $this;
	}

	/**
	 * @return \Rbs\Price\Tax\TaxApplication[]
	 */
	public function getTaxesValues()
	{
		return $this->taxesValues;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValue()
	{
		$price = null;
		foreach ($this->lines as $line)
		{
			$value = $line->getPriceValue();
			if ($value !== null)
			{
				$price += $value;
			}
		}
		return $price;
	}

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax()
	{
		$price = null;
		foreach ($this->lines as $line)
		{
			$value = $line->getPriceValueWithTax();
			if ($value !== null)
			{
				$price += $value;
			}
		}
		return $price;
	}

	public function getPaymentAmount()
	{
		return $this->getPriceValueWithTax();
	}

	/**
	 * @param array $shippingModes
	 * @return $this
	 */
	public function setShippingModes($shippingModes)
	{
		$this->shippingModes = $shippingModes;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getShippingModes()
	{
		return $this->shippingModes;
	}

	/**
	 * @param \Rbs\Commerce\Process\CouponInterface[] $coupons
	 * @return $this
	 */
	public function setCoupons($coupons)
	{
		$this->coupons = $coupons;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Process\CouponInterface[]
	 */
	public function getCoupons()
	{
		return $this->coupons;
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		$serializedData = array('identifier' => $this->identifier,
			'billingArea' => $this->billingArea,
			'zone' => $this->zone,
			'context' => $this->context,
			'lines' => $this->lines,
			'linesTaxesValues' => $this->linesTaxesValues,
			'errors' => $this->errors,
			'email' => $this->email,
			'address' => $this->address,
			'fees' => $this->fees,
			'discounts' => $this->discounts,
			'shippingModes' => $this->shippingModes,
			'coupons' => $this->coupons,
			'taxesValues' => $this->taxesValues,
		);
		return serialize((new CartStorage())->getSerializableValue($serializedData));
	}

	/**
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->serializedData = unserialize($serialized);
	}

	protected function restoreSerializedData()
	{
		$serializedData = (new CartStorage())->setDocumentManager($this->getDocumentManager())
			->restoreSerializableValue($this->serializedData);
		$this->serializedData = null;
		$this->identifier = $serializedData['identifier'];
		$this->billingArea = $serializedData['billingArea'];
		$this->zone = $serializedData['zone'];
		$this->context = $serializedData['context'];
		$this->lines = $serializedData['lines'];
		$this->linesTaxesValues = $serializedData['linesTaxesValues'];
		$this->errors = $serializedData['errors'];
		$this->email = $serializedData['email'];
		$this->address = $serializedData['address'];
		$this->fees = $serializedData['fees'];
		$this->discounts = $serializedData['discounts'];
		$this->shippingModes = $serializedData['shippingModes'];
		$this->coupons = $serializedData['coupons'];
		$this->taxesValues = $serializedData['taxesValues'];
		foreach ($this->lines as $line)
		{
			/* @var $line CartLine */
			$line->setDocumentManager($this->getDocumentManager());
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'identifier' => $this->identifier,
			'context' => $this->getContext()->toArray(),
			'errors' => [],
			'lastUpdate' => $this->lastUpdate->format(\DateTime::ISO8601),
			'webStoreId' => $this->webStoreId,
			'billingAreaId' => $this->billingArea ? $this->billingArea->getId() : null,
			'currencyCode' => $this->billingArea ? $this->billingArea->getCurrencyCode() : null,
			'taxes' => [],
			'zone' => $this->zone,
			'lines' => [],
			'linesTaxesValues' => [],
			'userId' => $this->userId,
			'ownerId' => $this->ownerId,
			'email' => $this->email,
			'address' => ($this->address instanceof \Rbs\Geo\Address\AddressInterface) ? $this->address->getFields() : null,
			'shippingModes' => [],
			'coupons' => [],
			'fees' => [], // TODO
			'discounts' => [], // TODO
			'taxesValues' => [],
			'creditNotes' => [], // TODO
			'locked' => $this->locked,
			'transactionId' => $this->transactionId,
			'paymentAmount' => $this->getPaymentAmount(),
			'ordered' => $this->ordered
		);

		foreach ($this->getTaxes() as $tax)
		{
			$array['taxes'][] = $tax->toArray();
		}

		foreach ($this->getTaxesValues() as $taxApplication)
		{
			$array['taxesValues'][] = $taxApplication->toArray();
		}

		foreach ($this->getLinesTaxesValues() as $taxApplication)
		{
			$array['linesTaxesValues'][] = $taxApplication->toArray();
		}

		foreach ($this->lines as $line)
		{
			$array['lines'][] = $line->toArray();
		}

		foreach ($this->shippingModes as $shippingMode)
		{
			$array['shippingModes'][] = $shippingMode->toArray();
		}

		foreach ($this->coupons as $coupon)
		{
			$array['coupons'][] = $coupon->toArray();
		}

		foreach ($this->errors as $error)
		{
			$array['errors'][] = $error->toArray();
		}
		return $array;
	}
}