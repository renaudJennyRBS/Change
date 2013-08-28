<?php
namespace Rbs\Commerce\Cart;

/**
* @name \Rbs\Commerce\Cart\CartReservation
*/
class CartReservation implements \Rbs\Stock\Interfaces\Reservation
{
	/**
	 * @var string
	 */
	protected $cartIdentifier;

	/**
	 * @var string
	 */
	protected $codeSku;

	/**
	 * @var integer
	 */
	protected $webStoreId;

	/**
	 * @var float
	 */
	protected $quantity;

	/**
	 * @var float
	 */
	protected $quantityNotReserved;

	/**
	 * @param string $cartIdentifier
	 * @param string $codeSku
	 */
	function __construct($cartIdentifier, $codeSku)
	{
		$this->cartIdentifier = $cartIdentifier;
		$this->codeSku = $codeSku;
	}

	/**
	 * @return string
	 */
	public function getTargetIdentifier()
	{
		return $this->cartIdentifier;
	}

	/**
	 * @return string
	 */
	public function getCodeSku()
	{
		return $this->codeSku;
	}

	/**
	 * @param float $quantity
	 * @return $this
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	/**
	 * @param float $add
	 * @return $this
	 */
	public function addQuantity($add)
	{
		$this->quantity += $add;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @param int $webStoreId
	 * @return $this
	 */
	public function setWebStoreId($webStoreId)
	{
		$this->webStoreId = $webStoreId;
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
	 * @param float $quantityNotReserved
	 * @return $this
	 */
	public function setQuantityNotReserved($quantityNotReserved)
	{
		$this->quantityNotReserved = $quantityNotReserved;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getQuantityNotReserved()
	{
		return $this->quantityNotReserved;
	}


}