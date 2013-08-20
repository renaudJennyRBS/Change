<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\Cart as CartInterfaces;
use Change\Documents\AbstractDocument;
use Change\Documents\DocumentWeakReference;

/**
* @name \Rbs\Commerce\Cart\Cart
*/
class Cart implements CartInterfaces
{
	/**
	 * @var \Rbs\Commerce\Services\CommerceServices
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
	 * @var \Rbs\Commerce\Interfaces\CartLine[]
	 */
	protected $lines = array();

	/**
	 * @var array|null
	 */
	protected $rawData;

	/**
	 * @param string $identifier
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
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
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices($commerceServices)
	{
		$this->commerceServices = $commerceServices;
		if ($commerceServices && $this->rawData)
		{
			$this->dispatchRawData();
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
	 * @return \Rbs\Commerce\Interfaces\CartLine[]
	 */
	public function getLines()
	{
		return $this->lines;
	}

	/**
	 * @return \Rbs\Commerce\Interfaces\CartItem[]
	 */
	public function getItems()
	{
		$items = array();
		foreach ($this->getLines() as $line)
		{
			array_merge($items, $line->getItems());
		}
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
		$rawData = array('identifier' =>  $this->getIdentifier(),
			'billingArea' => $this->getBillingArea(),
			'zone' => $this->getZone(),
			'context' => $this->getContext(),
			'lines' => $this->getLines());
		return serialize($this->getSerializableValue($rawData));
	}

	/**
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->rawData = unserialize($serialized);
	}

	protected function dispatchRawData()
	{
		$rawData = $this->restoreSerializableValue($this->rawData);
		$this->rawData = null;
		$this->identifier = $rawData['identifier'];
		$this->billingArea = $rawData['billingArea'];
		$this->zone = $rawData['zone'];
		$this->context = $rawData['context'];
		$this->lines = $rawData['lines'];
	}

	/**
	 * @param mixed $value
	 * @return array|\Change\Documents\DocumentWeakReference|mixed
	 */
	public function getSerializableValue($value)
	{
		if ($value instanceof AbstractDocument)
		{
			return new DocumentWeakReference($value);
		}
		elseif (is_array($value))
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = $this->getSerializableValue($v);
			}
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 * @return array|\Change\Documents\DocumentWeakReference|mixed
	 */
	public function restoreSerializableValue($value)
	{
		if ($value instanceof DocumentWeakReference)
		{
			return $value->getDocument($this->getCommerceServices()->getDocumentServices()->getDocumentManager());
		}
		elseif (is_array($value))
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = $this->restoreSerializableValue($v);
			}
		}
		return $value;
	}
}