<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\Cart as CartInterfaces;
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
	 * @param $lineKey
	 * @param string $designation
	 * @param float $quantity
	 * @param array $options
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function getNewLine($lineKey, $designation = null, $quantity = 1.0, array $options = null)
	{
		$line = new CartLine($lineKey);
		$line->setDesignation($designation);
		$line->setQuantity(floatval($quantity));
		if (is_array($options))
		{
			$line->getOptions()->fromArray($options);
		}
		return $line;
	}

	/**
	 * @param CartLine $line
	 * @param integer $lineNumber
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function insertLineAt(CartLine $line, $lineNumber = 1)
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

	/**
	 * @param CartLine $line
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Cart\CartLine
	 */
	public function appendLine(CartLine $line)
	{
		if ($this->getLineByKey($line->getKey()))
		{
			throw new \RuntimeException('Duplicate line key: ' . $line->getKey(), 999999);
		}
		$this->lines[] = $line;
		$line->setNumber(count($this->lines));
		return $line;
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
	 * @return CartItem[]
	 */
	public function getItems()
	{
		$items = array();
		foreach ($this->getLines() as $line)
		{
			$items = array_merge($items, $line->getItems());
		}
		return $items;
	}

	/**
	 * @param string $codeSKU
	 * @param float $priceValue
	 * @param TaxApplication|TaxApplication[] $taxApplication
	 * @param float $reservationQuantity
	 * @param array $options
	 * @throws \InvalidArgumentException
	 * @return CartItem
	 */
	public function getNewItem($codeSKU, $priceValue = 0.0, $taxApplication = null, $reservationQuantity = 1.0,
		array $options = null)
	{
		$item = new CartItem($codeSKU);
		$item->setPriceValue(floatval($priceValue));
		$item->setReservationQuantity(floatval($reservationQuantity));
		if (is_array($options))
		{
			$item->getOptions()->fromArray($options);
		}

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
				else
				{
					throw new \InvalidArgumentException('Argument 3 should be a TaxApplication[]', 999999);
				}
			}
		}
		elseif ($taxApplication !== null)
		{
			throw new \InvalidArgumentException('Argument 3 should be a TaxApplication', 999999);
		}
		return $item;
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
}