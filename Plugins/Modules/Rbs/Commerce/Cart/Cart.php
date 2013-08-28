<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\Cart as CartInterfaces;
use Rbs\Commerce\Interfaces\CartItem as CartItemInterfaces;
use Rbs\Commerce\Interfaces\CartLine as CartLineInterfaces;

use Rbs\Commerce\Interfaces\TaxApplication;
use Rbs\Commerce\Services\CommerceServices;

/**
 * @name \Rbs\Commerce\Cart\Cart
 */
class Cart implements CartInterfaces
{
	/**
	 * @var CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var \Rbs\Commerce\Interfaces\BillingArea
	 */
	protected $billingArea;

	/**
	 * @var string
	 */
	protected $zone;

	/**
	 * @var integer
	 */
	protected $ownerId = 0;

	/**
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * @var \DateTime
	 */
	protected $lastUpdate;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $context;

	/**
	 * @var \Rbs\Commerce\Cart\CartLine[]
	 */
	protected $lines = array();

	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @param string $identifier
	 * @param CommerceServices $commerceServices
	 */
	function __construct($identifier, $commerceServices)
	{
		$this->identifier = $identifier;
		$this->commerceServices = $commerceServices;
	}

	/**
	 * @return \Rbs\Commerce\Services\CommerceServices
	 */
	public function getCommerceServices()
	{
		return $this->commerceServices;
	}

	/**
	 * @param CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices($commerceServices)
	{
		$this->commerceServices = $commerceServices;
		if ($commerceServices && $this->serializedData)
		{
			$this->restoreSerializedData();
		}
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
	 * @param int|null $ownerId
	 * @return $this
	 */
	public function setOwnerId($ownerId)
	{
		$this->ownerId = $ownerId;
		return $this;
	}

	/**
	 * @return integer|null
	 */
	public function getOwnerId()
	{
		return $this->ownerId;
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
	 * @param \Rbs\Commerce\Interfaces\CartLineConfig $cartLineConfig
	 * @param float $quantity
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function getNewLine(\Rbs\Commerce\Interfaces\CartLineConfig $cartLineConfig, $quantity)
	{
		$line = new CartLine($cartLineConfig->getKey());
		$line->setDesignation($cartLineConfig->getDesignation());
		$line->setQuantity($quantity);
		if (is_array($cartLineConfig->getOptions()))
		{
			$line->getOptions()->fromArray($cartLineConfig->getOptions());
		}
		$itemConfig = $cartLineConfig->getItemConfigArray();
		if ($itemConfig instanceof \Rbs\Commerce\Interfaces\CartItemConfig)
		{
			$line->appendItem($this->getNewItem($itemConfig));
		}
		elseif (is_array($itemConfig))
		{
			foreach ($itemConfig as $ic)
			{
				if ($ic instanceof \Rbs\Commerce\Interfaces\CartItemConfig)
				{
					$line->appendItem($this->getNewItem($ic));
				}
			}
		}
		return $line;
	}

	/**
	 * @param CartLineInterfaces $line
	 * @param integer $lineNumber
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function insertLineAt(CartLineInterfaces $line, $lineNumber = 1)
	{
		if ($line instanceof CartLine)
		{
			$lastLineNumber = count($this->lines);
			if ($lineNumber < 1 || $lineNumber > $lastLineNumber)
			{
				return $this->appendLine($line);
			}
			if ($this->getLineByKey($line->getKey()))
			{
				throw new \RuntimeException('Duplicate line key: ' . $line->getKey(), 999999);
			}
			$idx = $lineNumber - 1;
			$this->lines = array_merge(array_slice($this->lines, 0, $idx), array($line), array_slice($this->lines, $idx));
			for (; $idx <= $lastLineNumber; $idx++)
			{
				/* @var $l CartLine */
				$l = $this->lines[$idx];
				$l->setNumber($idx + 1);
			}
			return $line;
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 should be a CartLine', 999999);
		}
	}

	/**
	 * @param CartLineInterfaces $line
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function appendLine(CartLineInterfaces $line)
	{
		if ($line instanceof CartLine)
		{
			if ($this->getLineByKey($line->getKey()))
			{
				throw new \RuntimeException('Duplicate line key: ' . $line->getKey(), 999999);
			}
			$this->lines[] = $line;
			$line->setNumber(count($this->lines));
			return $line;
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 should be a CartLine', 999999);
		}
	}

	/**
	 * @param integer $lineNumber
	 * @return \Rbs\Commerce\Cart\CartLine|null
	 */
	public function removeLineByNumber($lineNumber)
	{
		$lastLineNumber = count($this->lines);
		if ($lineNumber < 1 || $lineNumber > $lastLineNumber)
		{
			return null;
		}
		$idx = $lineNumber - 1;
		$line = $this->lines[$idx];
		$this->lines = array_merge(array_slice($this->lines, 0, $idx), array_slice($this->lines, $idx + 1));
		$lastLineNumber--;
		for (; $idx < $lastLineNumber; $idx++)
		{
			/* @var $l CartLine */
			$l = $this->lines[$idx];
			$l->setNumber($idx + 1);
		}
		return $line;
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
			return $this->removeLineByNumber($line->getNumber());
		}
		return $line;
	}

	/**
	 * @param string $lineKey
	 * @param float $newQuantity
	 * @return CartLine|null
	 */
	public function updateLineQuantity($lineKey, $newQuantity)
	{
		$line = $this->getLineByKey($lineKey);
		if ($line)
		{
			return $line->setQuantity(floatval($newQuantity));
		}
		return $line;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\CartItemConfig $cartItemConfig
	 * @return CartItem
	 */
	public function getNewItem(\Rbs\Commerce\Interfaces\CartItemConfig $cartItemConfig)
	{
		$item = new CartItem($cartItemConfig->getCodeSKU());
		$item->setPriceValue($cartItemConfig->getPriceValue());
		$item->setReservationQuantity(floatval($cartItemConfig->getReservationQuantity()));
		if (is_array($cartItemConfig->getOptions()))
		{
			$item->getOptions()->fromArray($cartItemConfig->getOptions());
		}

		$taxApplication = $cartItemConfig->getTaxApplication();
		if ($taxApplication instanceof TaxApplication)
		{
			$cartTax = new CartTax();
			$cartTax->fromTaxApplication($taxApplication);
			$item->appendCartTaxes($cartTax);
		}
		elseif (is_array($taxApplication))
		{
			foreach ($taxApplication as $taxApp)
			{
				if ($taxApp instanceof TaxApplication)
				{
					$cartTax = new CartTax();
					$cartTax->fromTaxApplication($taxApp);
					$item->appendCartTaxes($cartTax);
				}
			}
		}
		return $item;
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\CartItem $item
	 * @param float $priceValue
	 * @return \Rbs\Commerce\Interfaces\CartItem
	 */
	public function updateItemPrice($item, $priceValue)
	{
		if ($item instanceof CartItem)
		{
			$item->setPriceValue($priceValue);
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 should be a CartItem', 999999);
		}
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\CartItem $item
	 * @param TaxApplication[] $taxApplicationArray
	 * @return \Rbs\Commerce\Interfaces\CartItem
	 */
	public function updateItemTaxes($item, $taxApplicationArray)
	{
		if ($item instanceof CartItem)
		{
			$cartTaxes = array();
			foreach ($taxApplicationArray as $taxApplication)
			{
				if ($taxApplication instanceof TaxApplication)
				{
					$cartTax = new CartTax();
					$cartTax->fromTaxApplication($taxApplication);
					$cartTaxes[] = $cartTax;
				}
			}
			$item->setCartTaxes($cartTaxes);
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 should be a CartItem', 999999);
		}
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\CartTax[]
	 */
	public function getTaxes()
	{
		/* @var $taxes CartTax[] */
		$taxes = array();
		foreach ($this->lines as $line)
		{
			$qtt = $line->getQuantity();
			foreach ($line->getItems() as $item)
			{
				foreach ($item->getCartTaxes() as $cartTax)
				{
					$key = ($cartTax->getTax() ? $cartTax->getTax()->getCode() : '') . '/' . $cartTax->getCategory();
					if (!isset($taxes[$key]))
					{
						$tax = clone($cartTax);
						$tax->setValue($tax->getValue() * $qtt);
						$taxes[$key] = $tax;
					}
					else
					{
						$tax = $taxes[$key];
						$tax->addValue($cartTax->getValue() * $qtt);
					}
				}
			}
		}
		return array_values($taxes);
	}

	/**
	 * @param \Rbs\Commerce\Interfaces\BillingArea $billingArea
	 * @return $this
	 */
	public function setBillingArea($billingArea)
	{
		$this->billingArea = $billingArea;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\BillingArea
	 */
	public function getBillingArea()
	{
		return $this->billingArea;
	}

	/**
	 * @param string $zone
	 * @return $this
	 */
	public function setZone($zone)
	{
		$this->zone = $zone;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getZone()
	{
		return $this->zone;
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
			'ownerId' => $this->ownerId,
			'context' => $this->context,
			'lines' => $this->lines);
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
		$serializedData = (new CartStorage())->restoreSerializableValue($this->serializedData, $this->getCommerceServices());
		$this->serializedData = null;
		$this->identifier = $serializedData['identifier'];
		$this->billingArea = $serializedData['billingArea'];
		$this->zone = $serializedData['zone'];
		$this->ownerId = $serializedData['ownerId'];
		$this->context = $serializedData['context'];
		$this->lines = $serializedData['lines'];
		foreach ($this->lines as $line)
		{
			/* @var $line CartLine */
			$line->setCart($this);
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'identifier' => $this->identifier,
			'billingArea' => $this->billingArea ? $this->billingArea->getCode() : null,
			'zone' => $this->zone,
			'locked' => $this->locked,
			'lastUpdate' => $this->lastUpdate->format(\DateTime::ISO8601),
			'ownerId' => $this->ownerId,
			'context' => $this->getContext()->toArray(),
			'lines' => array());

		foreach($this->lines as $line)
		{
			$array['lines'][] = $line->toArray();
		}
		return $array;
	}
}