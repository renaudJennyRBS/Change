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
	protected $codeSku;

	/**
	 * @var integer
	 */
	protected $webStoreId;

	/**
	 * @var integer
	 */
	protected $quantity;

	/**
	 * @var integer
	 */
	protected $quantityNotReserved;

	/**
	 * @param string $codeSku
	 * @param integer $webStoreId
	 */
	public function __construct($codeSku, $webStoreId)
	{
		$this->codeSku = $codeSku;
		$this->webStoreId = $webStoreId;
	}

	/**
	 * @return string
	 */
	public function getCodeSku()
	{
		return $this->codeSku;
	}

	/**
	 * @param integer $quantity
	 * @return $this
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	/**
	 * @param integer $add
	 * @return $this
	 */
	public function addQuantity($add)
	{
		$this->quantity += $add;
		return $this;
	}

	/**
	 * @return integer
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
	 * @param integer $quantityNotReserved
	 * @return $this
	 */
	public function setQuantityNotReserved($quantityNotReserved)
	{
		$this->quantityNotReserved = $quantityNotReserved;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getQuantityNotReserved()
	{
		return $this->quantityNotReserved;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->codeSku .'/' . $this->webStoreId;
	}

	/**
	 * @param \Rbs\Stock\Interfaces\Reservation $reservation
	 * @return boolean
	 */
	public function isSame($reservation)
	{
		if ($reservation instanceof \Rbs\Stock\Interfaces\Reservation)
		{
			return $this->getKey() == ($reservation->getCodeSku() . '/' . $reservation->getWebStoreId());
		}
		return false;
	}
}